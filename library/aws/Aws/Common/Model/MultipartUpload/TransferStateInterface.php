<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Client\AwsClientInterface;
interface TransferStateInterface extends \Countable, \IteratorAggregate, \Serializable
{
    public static function fromUploadId(AwsClientInterface $client, UploadIdInterface $uploadId);
    public function getUploadId();
    public function getPart($partNumber);
    public function addPart(UploadPartInterface $part);
    public function hasPart($partNumber);
    public function getPartNumbers();
    public function setAborted($aborted);
    public function isAborted();
}
