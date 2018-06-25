<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Model\MultipartUpload\AbstractTransferState;
use Aws\Common\Model\MultipartUpload\UploadIdInterface;
class TransferState extends AbstractTransferState
{
    public static function fromUploadId(AwsClientInterface $client, UploadIdInterface $uploadId)
    {
        $transferState = new self($uploadId);
        foreach ($client->getIterator('ListParts', $uploadId->toParams()) as $part) {
            $transferState->addPart(UploadPart::fromArray($part));
        }
        return $transferState;
    }
}
