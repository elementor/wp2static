<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Exception\RuntimeException;
use Aws\Common\Model\MultipartUpload\AbstractTransfer as CommonAbstractTransfer;
use Guzzle\Service\Command\OperationCommand;
abstract class AbstractTransfer extends CommonAbstractTransfer
{
    const MIN_PART_SIZE = 5242880;
    const MAX_PART_SIZE = 5368709120;
    const MAX_PARTS     = 10000;
    protected function init()
    {
        $this->options = array_replace(array(
            'min_part_size' => self::MIN_PART_SIZE,
            'part_md5'      => true
        ), $this->options);
        if (!$this->options['min_part_size'] && !$this->source->getContentLength()) {
            throw new RuntimeException('The ContentLength of the data source could not be determined, and no '
                . 'min_part_size option was provided');
        }
    }
    protected function calculatePartSize()
    {
        $partSize = $this->source->getContentLength()
            ? (int) ceil(($this->source->getContentLength() / self::MAX_PARTS))
            : self::MIN_PART_SIZE;
        $partSize = max($this->options['min_part_size'], $partSize);
        $partSize = min($partSize, self::MAX_PART_SIZE);
        $partSize = max($partSize, self::MIN_PART_SIZE);
        return $partSize;
    }
    protected function complete()
    {
        $parts = array();
        foreach ($this->state as $part) {
            $parts[] = array(
                'PartNumber' => $part->getPartNumber(),
                'ETag'       => $part->getETag(),
            );
        }
        $params = $this->state->getUploadId()->toParams();
        $params[Ua::OPTION] = Ua::MULTIPART_UPLOAD;
        $params['Parts'] = $parts;
        $command = $this->client->getCommand('CompleteMultipartUpload', $params);
        return $command->getResult();
    }
    protected function getAbortCommand()
    {
        $params = $this->state->getUploadId()->toParams();
        $params[Ua::OPTION] = Ua::MULTIPART_UPLOAD;
        $command = $this->client->getCommand('AbortMultipartUpload', $params);
        return $command;
    }
}
