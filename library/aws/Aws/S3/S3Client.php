<?php
namespace Aws\S3;
use Aws\Api\ApiProvider;
use Aws\Api\DocModel;
use Aws\Api\Service;
use Aws\AwsClient;
use Aws\ClientResolver;
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\HandlerList;
use Aws\Middleware;
use Aws\RetryMiddleware;
use Aws\ResultInterface;
use Aws\CommandInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
class S3Client extends AwsClient implements S3ClientInterface
{
    use S3ClientTrait;
    public static function getArguments()
    {
        $args = parent::getArguments();
        $args['retries']['fn'] = [__CLASS__, '_applyRetryConfig'];
        $args['api_provider']['fn'] = [__CLASS__, '_applyApiProvider'];
        return $args + [
            'bucket_endpoint' => [
                'type'    => 'config',
                'valid'   => ['bool'],
                'doc'     => 'Set to true to send requests to a hardcoded '
                    . 'bucket endpoint rather than create an endpoint as a '
                    . 'result of injecting the bucket into the URL. This '
                    . 'option is useful for interacting with CNAME endpoints.',
            ],
            'use_accelerate_endpoint' => [
                'type' => 'config',
                'valid' => ['bool'],
                'doc' => 'Set to true to send requests to an S3 Accelerate'
                    . ' endpoint by default. Can be enabled or disabled on'
                    . ' individual operations by setting'
                    . ' \'@use_accelerate_endpoint\' to true or false. Note:'
                    . ' you must enable S3 Accelerate on a bucket before it can'
                    . ' be accessed via an Accelerate endpoint.',
                'default' => false,
            ],
            'use_dual_stack_endpoint' => [
                'type' => 'config',
                'valid' => ['bool'],
                'doc' => 'Set to true to send requests to an S3 Dual Stack'
                    . ' endpoint by default, which enables IPv6 Protocol.'
                    . ' Can be enabled or disabled on individual operations by setting'
                    . ' \'@use_dual_stack_endpoint\' to true or false.',
                'default' => false,
            ],
            'use_path_style_endpoint' => [
                'type' => 'config',
                'valid' => ['bool'],
                'doc' => 'Set to true to send requests to an S3 path style'
                    . ' endpoint by default.'
                    . ' Can be enabled or disabled on individual operations by setting'
                    . ' \'@use_path_style_endpoint\' to true or false.',
                'default' => false,
            ],
        ];
    }
    public function __construct(array $args)
    {
        parent::__construct($args);
        $stack = $this->getHandlerList();
        $stack->appendInit(SSECMiddleware::wrap($this->getEndpoint()->getScheme()), 's3.ssec');
        $stack->appendBuild(ApplyChecksumMiddleware::wrap(), 's3.checksum');
        $stack->appendBuild(
            Middleware::contentType(['PutObject', 'UploadPart']),
            's3.content_type'
        );
        if ($this->getConfig('bucket_endpoint')) {
            $stack->appendBuild(BucketEndpointMiddleware::wrap(), 's3.bucket_endpoint');
        } else {
            $stack->appendBuild(
                S3EndpointMiddleware::wrap(
                    $this->getRegion(),
                    [
                        'dual_stack' => $this->getConfig('use_dual_stack_endpoint'),
                        'accelerate' => $this->getConfig('use_accelerate_endpoint'),
                        'path_style' => $this->getConfig('use_path_style_endpoint')
                    ]
                ),
                's3.endpoint_middleware'
            );
        }
        $stack->appendSign(PutObjectUrlMiddleware::wrap(), 's3.put_object_url');
        $stack->appendSign(PermanentRedirectMiddleware::wrap(), 's3.permanent_redirect');
        $stack->appendInit(Middleware::sourceFile($this->getApi()), 's3.source_file');
        $stack->appendInit($this->getSaveAsParameter(), 's3.save_as');
        $stack->appendInit($this->getLocationConstraintMiddleware(), 's3.location');
        $stack->appendInit($this->getEncodingTypeMiddleware(), 's3.auto_encode');
        $stack->appendInit($this->getHeadObjectMiddleware(), 's3.head_object');
    }
    public static function isBucketDnsCompatible($bucket)
    {
        $bucketLen = strlen($bucket);
        return ($bucketLen >= 3 && $bucketLen <= 63) &&
            !filter_var($bucket, FILTER_VALIDATE_IP) &&
            preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/', $bucket);
    }
    public function createPresignedRequest(CommandInterface $command, $expires)
    {
        $command = clone $command;
        $command->getHandlerList()->remove('signer');
        $signer = call_user_func(
            $this->getSignatureProvider(),
            $this->getConfig('signature_version'),
            $this->getConfig('signing_name'),
            $this->getConfig('signing_region')
        );
        return $signer->presign(
            \Aws\serialize($command),
            $this->getCredentials()->wait(),
            $expires
        );
    }
    public function getObjectUrl($bucket, $key)
    {
        $command = $this->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $key
        ]);
        return (string) \Aws\serialize($command)->getUri();
    }
    public static function encodeKey($key)
    {
        return str_replace('%2F', '/', rawurlencode($key));
    }
    private function getLocationConstraintMiddleware()
    {
        $region = $this->getRegion();
        return static function (callable $handler) use ($region) {
            return function (Command $command, $request = null) use ($handler, $region) {
                if ($command->getName() === 'CreateBucket') {
                    $locationConstraint = isset($command['CreateBucketConfiguration']['LocationConstraint'])
                        ? $command['CreateBucketConfiguration']['LocationConstraint']
                        : null;
                    if ($locationConstraint === 'us-east-1') {
                        unset($command['CreateBucketConfiguration']);
                    } elseif ('us-east-1' !== $region && empty($locationConstraint)) {
                        $command['CreateBucketConfiguration'] = ['LocationConstraint' => $region];
                    }
                }
                return $handler($command, $request);
            };
        };
    }
    private function getSaveAsParameter()
    {
        return static function (callable $handler) {
            return function (Command $command, $request = null) use ($handler) {
                if ($command->getName() === 'GetObject' && isset($command['SaveAs'])) {
                    $command['@http']['sink'] = $command['SaveAs'];
                    unset($command['SaveAs']);
                }
                return $handler($command, $request);
            };
        };
    }
    private function getHeadObjectMiddleware()
    {
        return static function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                if ($command->getName() === 'HeadObject'
                    && !isset($command['@http']['decode_content'])
                ) {
                    $command['@http']['decode_content'] = false;
                }
                return $handler($command, $request);
            };
        };
    }
    private function getEncodingTypeMiddleware()
    {
        return static function (callable $handler) {
            return function (Command $command, $request = null) use ($handler) {
                $autoSet = false;
                if ($command->getName() === 'ListObjects'
                    && empty($command['EncodingType'])
                ) {
                    $command['EncodingType'] = 'url';
                    $autoSet = true;
                }
                return $handler($command, $request)
                    ->then(function (ResultInterface $result) use ($autoSet) {
                        if ($result['EncodingType'] === 'url' && $autoSet) {
                            static $topLevel = [
                                'Delimiter',
                                'Marker',
                                'NextMarker',
                                'Prefix',
                            ];
                            static $nested = [
                                ['Contents', 'Key'],
                                ['CommonPrefixes', 'Prefix'],
                            ];
                            foreach ($topLevel as $key) {
                                if (isset($result[$key])) {
                                    $result[$key] = urldecode($result[$key]);
                                }
                            }
                            foreach ($nested as $steps) {
                                if (isset($result[$steps[0]])) {
                                    foreach ($result[$steps[0]] as $key => $part) {
                                        if (isset($part[$steps[1]])) {
                                            $result[$steps[0]][$key][$steps[1]]
                                                = urldecode($part[$steps[1]]);
                                        }
                                    }
                                }
                            }
                        }
                        return $result;
                    });
            };
        };
    }
    public static function _applyRetryConfig($value, $_, HandlerList $list)
    {
        if (!$value) {
            return;
        }
        $decider = RetryMiddleware::createDefaultDecider($value);
        $decider = function ($retries, $command, $request, $result, $error) use ($decider, $value) {
            $maxRetries = null !== $command['@retries']
                ? $command['@retries']
                : $value;
            if ($decider($retries, $command, $request, $result, $error)) {
                return true;
            }
            if ($error instanceof AwsException
                && $retries < $maxRetries
            ) {
                if ($error->getResponse()
                    && $error->getResponse()->getStatusCode() >= 400
                ) {
                    return strpos(
                            $error->getResponse()->getBody(),
                            'Your socket connection to the server'
                        ) !== false;
                }
                if ($error->getPrevious() instanceof RequestException) {
                    return $command->getName() !== 'CompleteMultipartUpload';
                }
            }
            return false;
        };
        $delay = [RetryMiddleware::class, 'exponentialDelay'];
        $list->appendSign(Middleware::retry($decider, $delay), 'retry');
    }
    public static function _applyApiProvider($value, array &$args, HandlerList $list)
    {
        ClientResolver::_apply_api_provider($value, $args, $list);
        $args['parser'] = new GetBucketLocationParser(
            new AmbiguousSuccessParser(
                new RetryableMalformedResponseParser(
                    $args['parser'],
                    $args['exception_class']
                ),
                $args['error_parser'],
                $args['exception_class']
            )
        );
    }
    public static function applyDocFilters(array $api, array $docs)
    {
        $b64 = '<div class="alert alert-info">This value will be base64 encoded on your behalf.</div>';
        $opt = '<div class="alert alert-info">This value will be computed for you it is not supplied.</div>';
        $docs['shapes']['SourceFile']['base'] = 'The path to a file on disk to use instead of the Body parameter.';
        $api['shapes']['SourceFile'] = ['type' => 'string'];
        $api['shapes']['PutObjectRequest']['members']['SourceFile'] = ['shape' => 'SourceFile'];
        $api['shapes']['UploadPartRequest']['members']['SourceFile'] = ['shape' => 'SourceFile'];
        $docs['shapes']['ContentSHA256']['base'] = 'A SHA256 hash of the body content of the request.';
        $api['shapes']['ContentSHA256'] = ['type' => 'string'];
        $api['shapes']['PutObjectRequest']['members']['ContentSHA256'] = ['shape' => 'ContentSHA256'];
        $api['shapes']['UploadPartRequest']['members']['ContentSHA256'] = ['shape' => 'ContentSHA256'];
        unset($api['shapes']['PutObjectRequest']['members']['ContentMD5']);
        unset($api['shapes']['UploadPartRequest']['members']['ContentMD5']);
        $docs['shapes']['ContentSHA256']['append'] = $opt;
        $docs['shapes']['SaveAs']['base'] = 'The path to a file on disk to save the object data.';
        $api['shapes']['SaveAs'] = ['type' => 'string'];
        $api['shapes']['GetObjectRequest']['members']['SaveAs'] = ['shape' => 'SaveAs'];
        $docs['shapes']['SSECustomerKey']['append'] = $b64;
        $docs['shapes']['CopySourceSSECustomerKey']['append'] = $b64;
        $docs['shapes']['SSECustomerKeyMd5']['append'] = $opt;
        $docs['shapes']['ObjectURL']['base'] = 'The URI of the created object.';
        $api['shapes']['ObjectURL'] = ['type' => 'string'];
        $api['shapes']['PutObjectOutput']['members']['ObjectURL'] = ['shape' => 'ObjectURL'];
        $api['shapes']['CopyObjectOutput']['members']['ObjectURL'] = ['shape' => 'ObjectURL'];
        $api['shapes']['CompleteMultipartUploadOutput']['members']['ObjectURL'] = ['shape' => 'ObjectURL'];
        unset($api['shapes']['CreateBucketRequest']['payload']);
        $api['shapes']['BucketLocationConstraint']['enum'] = [
            "ap-northeast-1",
            "ap-southeast-2",
            "ap-southeast-1",
            "cn-north-1",
            "eu-central-1",
            "eu-west-1",
            "us-east-1",
            "us-west-1",
            "us-west-2",
            "sa-east-1",
        ];
        $docs['shapes']['ContentMD5']['append'] = '<div class="alert alert-info">The value will be computed on '
            . 'your behalf.</div>';
        return [
            new Service($api, ApiProvider::defaultProvider()),
            new DocModel($docs)
        ];
    }
}
