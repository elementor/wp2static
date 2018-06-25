<?php
namespace Aws\S3;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
class S3Signature implements S3SignatureInterface
{
    protected $signableQueryString = array (
        'acl',
        'cors',
        'delete',
        'lifecycle',
        'location',
        'logging',
        'notification',
        'partNumber',
        'policy',
        'requestPayment',
        'response-cache-control',
        'response-content-disposition',
        'response-content-encoding',
        'response-content-language',
        'response-content-type',
        'response-expires',
        'restore',
        'tagging',
        'torrent',
        'uploadId',
        'uploads',
        'versionId',
        'versioning',
        'versions',
        'website',
    );
    private $signableHeaders = array('Content-MD5', 'Content-Type');
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        sort($this->signableQueryString);
        if ($token = $credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $token);
        }
        $request->removeHeader('x-amz-date');
        $request->setHeader('Date', gmdate(\DateTime::RFC2822));
        $stringToSign = $this->createCanonicalizedString($request);
        $request->getParams()->set('aws.string_to_sign', $stringToSign);
        $request->setHeader(
            'Authorization',
            'AWS ' . $credentials->getAccessKeyId() . ':' . $this->signString($stringToSign, $credentials)
        );
    }
    public function createPresignedUrl(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        if ($expires instanceof \DateTime) {
            $expires = $expires->getTimestamp();
        } elseif (!is_numeric($expires)) {
            $expires = strtotime($expires);
        }
        $request = clone $request;
        $path = S3Client::encodeKey(rawurldecode($request->getPath()));
        $request->setPath($path);
        if ($token = $credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $token);
            $request->getQuery()->set('x-amz-security-token', $token);
        }
        $request->getQuery()
            ->set('AWSAccessKeyId', $credentials->getAccessKeyId())
            ->set('Expires', $expires)
            ->set('Signature', $this->signString(
                $this->createCanonicalizedString($request, $expires),
                $credentials
            ));
        foreach ($request->getHeaders() as $name => $header) {
            $name = strtolower($name);
            if (strpos($name, 'x-amz-') === 0) {
                $request->getQuery()->set($name, (string) $header);
                $request->removeHeader($name);
            }
        }
        return $request->getUrl();
    }
    public function signString($string, CredentialsInterface $credentials)
    {
        return base64_encode(hash_hmac('sha1', $string, $credentials->getSecretKey(), true));
    }
    public function createCanonicalizedString(RequestInterface $request, $expires = null)
    {
        $buffer = $request->getMethod() . "\n";
        foreach ($this->signableHeaders as $header) {
            $buffer .= (string) $request->getHeader($header) . "\n";
        }
        $date = $expires ?: (string) $request->getHeader('date');
        $buffer .= "{$date}\n"
            . $this->createCanonicalizedAmzHeaders($request)
            . $this->createCanonicalizedResource($request);
        return $buffer;
    }
    private function createCanonicalizedAmzHeaders(RequestInterface $request)
    {
        $headers = array();
        foreach ($request->getHeaders() as $name => $header) {
            $name = strtolower($name);
            if (strpos($name, 'x-amz-') === 0) {
                $value = trim((string) $header);
                if ($value || $value === '0') {
                    $headers[$name] = $name . ':' . $value;
                }
            }
        }
        if (!$headers) {
            return '';
        }
        ksort($headers);
        return implode("\n", $headers) . "\n";
    }
    private function createCanonicalizedResource(RequestInterface $request)
    {
        $buffer = $request->getParams()->get('s3.resource');
        if (null === $buffer) {
            $bucket = $request->getParams()->get('bucket') ?: $this->parseBucketName($request);
            $buffer = $bucket ? "/{$bucket}" : '';
            $path = S3Client::encodeKey(rawurldecode($request->getPath()));
            $buffer .= preg_replace("#^/{$bucket}/{$bucket}#", "/{$bucket}", $path);
        }
        $buffer = str_replace('
        $query = $request->getQuery();
        $first = true;
        foreach ($this->signableQueryString as $key) {
            if ($query->hasKey($key)) {
                $value = $query[$key];
                $buffer .= $first ? '?' : '&';
                $first = false;
                $buffer .= $key;
                if ($value !== '' &&
                    $value !== false &&
                    $value !== null &&
                    $value !== QueryString::BLANK
                ) {
                    $buffer .= "={$value}";
                }
            }
        }
        return $buffer;
    }
    private function parseBucketName(RequestInterface $request)
    {
        $baseUrl = Url::factory($request->getClient()->getBaseUrl());
        $baseHost = $baseUrl->getHost();
        $host = $request->getHost();
        if (strpos($host, $baseHost) === false) {
            $baseHost = '';
            $regions = $request->getClient()->getDescription()->getData('regions');
            foreach ($regions as $region) {
                if (strpos($host, $region['hostname']) !== false) {
                    $baseHost = $region['hostname'];
                    break;
                }
            }
            if (!$baseHost) {
                return $host;
            }
        }
        return trim(str_replace($baseHost, '', $request->getHost()), ' .');
    }
}
