<?php
namespace Aws\S3;
use Aws\Multipart\AbstractUploadManager;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;
class MultipartCopy extends AbstractUploadManager
{
    use MultipartUploadingTrait;
    private $source;
    private $sourceMetadata;
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        $this->source = '/' . ltrim($source, '/');
        parent::__construct($client, array_change_key_case($config) + [
            'source_metadata' => null
        ]);
    }
    public function copy()
    {
        return $this->upload();
    }
    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'CreateMultipartUpload',
                'upload'   => 'UploadPartCopy',
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
    protected function getUploadCommands(callable $resultHandler)
    {
        $parts = ceil($this->getSourceSize() / $this->determinePartSize());
        for ($partNumber = 1; $partNumber <= $parts; $partNumber++) {
            if (!$this->state->hasPartBeenUploaded($partNumber)) {
                $command = $this->client->getCommand(
                    $this->info['command']['upload'],
                    $this->createPart($partNumber, $parts)
                        + $this->getState()->getId()
                );
                $command->getHandlerList()->appendSign($resultHandler, 'mup');
                yield $command;
            }
        }
    }
    private function createPart($partNumber, $partsCount)
    {
        $data = [];
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        foreach ($params as $k => $v) {
            $data[$k] = $v;
        }
        $data['CopySource'] = $this->source;
        $data['PartNumber'] = $partNumber;
        $defaultPartSize = $this->determinePartSize();
        $startByte = $defaultPartSize * ($partNumber - 1);
        $data['ContentLength'] = $partNumber < $partsCount
            ? $defaultPartSize
            : $this->getSourceSize() - ($defaultPartSize * ($partsCount - 1));
        $endByte = $startByte + $data['ContentLength'] - 1;
        $data['CopySourceRange'] = "bytes=$startByte-$endByte";
        return $data;
    }
    protected function extractETag(ResultInterface $result)
    {
        return $result->search('CopyPartResult.ETag');
    }
    protected function getSourceMimeType()
    {
        return $this->getSourceMetadata()['ContentType'];
    }
    protected function getSourceSize()
    {
        return $this->getSourceMetadata()['ContentLength'];
    }
    private function getSourceMetadata()
    {
        if (empty($this->sourceMetadata)) {
            $this->sourceMetadata = $this->fetchSourceMetadata();
        }
        return $this->sourceMetadata;
    }
    private function fetchSourceMetadata()
    {
        if ($this->config['source_metadata'] instanceof ResultInterface) {
            return $this->config['source_metadata'];
        }
        list($bucket, $key) = explode('/', ltrim($this->source, '/'), 2);
        $headParams = [
            'Bucket' => $bucket,
            'Key' => $key,
        ];
        if (strpos($key, '?')) {
            list($key, $query) = explode('?', $key, 2);
            $headParams['Key'] = $key;
            $query = Psr7\parse_query($query, false);
            if (isset($query['versionId'])) {
                $headParams['VersionId'] = $query['versionId'];
            }
        }
        return $this->client->headObject($headParams);
    }
}
