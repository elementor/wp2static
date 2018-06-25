<?php
namespace Aws\S3;
use Aws\CommandInterface;
use Aws\Multipart\UploadState;
use Aws\ResultInterface;
trait MultipartUploadingTrait
{
    public static function getStateFromService(
        S3ClientInterface $client,
        $bucket,
        $key,
        $uploadId
    ) {
        $state = new UploadState([
            'Bucket'   => $bucket,
            'Key'      => $key,
            'UploadId' => $uploadId,
        ]);
        foreach ($client->getPaginator('ListParts', $state->getId()) as $result) {
            if (!$state->getPartSize()) {
                $state->setPartSize($result->search('Parts[0].Size'));
            }
            foreach ($result['Parts'] as $part) {
                $state->markPartAsUploaded($part['PartNumber'], [
                    'PartNumber' => $part['PartNumber'],
                    'ETag'       => $part['ETag']
                ]);
            }
        }
        $state->setStatus(UploadState::INITIATED);
        return $state;
    }
    protected function handleResult(CommandInterface $command, ResultInterface $result)
    {
        $this->getState()->markPartAsUploaded($command['PartNumber'], [
            'PartNumber' => $command['PartNumber'],
            'ETag'       => $this->extractETag($result),
        ]);
    }
    abstract protected function extractETag(ResultInterface $result);
    protected function getCompleteParams()
    {
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        $params['MultipartUpload'] = [
            'Parts' => $this->getState()->getUploadedParts()
        ];
        return $params;
    }
    protected function determinePartSize()
    {
        $partSize = $this->getConfig()['part_size'] ?: MultipartUploader::PART_MIN_SIZE;
        if ($sourceSize = $this->getSourceSize()) {
            $partSize = (int) max(
                $partSize,
                ceil($sourceSize / MultipartUploader::PART_MAX_NUM)
            );
        }
        if ($partSize < MultipartUploader::PART_MIN_SIZE || $partSize > MultipartUploader::PART_MAX_SIZE) {
            throw new \InvalidArgumentException('The part size must be no less '
                . 'than 5 MB and no greater than 5 GB.');
        }
        return $partSize;
    }
    protected function getInitiateParams()
    {
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        if (isset($config['acl'])) {
            $params['ACL'] = $config['acl'];
        }
        if (empty($params['ContentType']) && $type = $this->getSourceMimeType()) {
            $params['ContentType'] = $type;
        }
        return $params;
    }
    abstract protected function getState();
    abstract protected function getConfig();
    abstract protected function getSourceSize();
    abstract protected function getSourceMimeType();
}
