<?php
namespace Aws\S3;
use Aws\HashingStream;
use Aws\Multipart\AbstractUploader;
use Aws\PhpHash;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface as Stream;
use Aws\S3\Exception\S3MultipartUploadException;
class MultipartUploader extends AbstractUploader
{
    use MultipartUploadingTrait;
    const PART_MIN_SIZE = 5242880;
    const PART_MAX_SIZE = 5368709120;
    const PART_MAX_NUM = 10000;
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        parent::__construct($client, $source, array_change_key_case($config) + [
            'bucket' => null,
            'key'    => null,
            'exception_class' => S3MultipartUploadException::class,
        ]);
    }
    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'CreateMultipartUpload',
                'upload'   => 'UploadPart',
                'complete' => 'CompleteMultipartUpload',
            ],
            'id' => [
                'bucket'    => 'Bucket',
                'key'       => 'Key',
                'upload_id' => 'UploadId',
            ],
            'part_num' => 'PartNumber',
        ];
    }
    protected function createPart($seekable, $number)
    {
        $data = [];
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        foreach ($params as $k => $v) {
            $data[$k] = $v;
        }
        $data['PartNumber'] = $number;
        if ($seekable) {
            $body = $this->limitPartStream(
                new Psr7\LazyOpenStream($this->source->getMetadata('uri'), 'r')
            );
        } else {
            $source = $this->limitPartStream($this->source);
            $source = $this->decorateWithHashes($source, $data);
            $body = Psr7\stream_for();
            Psr7\copy_to_stream($source, $body);
        }
        $contentLength = $body->getSize();
        if ($contentLength === 0) {
            return false;
        }
        $body->seek(0);
        $data['Body'] = $body;
        $data['ContentLength'] = $contentLength;
        return $data;
    }
    protected function extractETag(ResultInterface $result)
    {
        return $result['ETag'];
    }
    protected function getSourceMimeType()
    {
        if ($uri = $this->source->getMetadata('uri')) {
            return Psr7\mimetype_from_filename($uri)
                ?: 'application/octet-stream';
        }
    }
    protected function getSourceSize()
    {
        return $this->source->getSize();
    }
    private function decorateWithHashes(Stream $stream, array &$data)
    {
        $hash = new PhpHash('sha256');
        return new HashingStream($stream, $hash, function ($result) use (&$data) {
            $data['ContentSHA256'] = bin2hex($result);
        });
    }
}
