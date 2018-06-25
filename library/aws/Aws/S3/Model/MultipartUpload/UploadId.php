<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Model\MultipartUpload\AbstractUploadId;
class UploadId extends AbstractUploadId
{
    protected static $expectedValues = array(
        'Bucket'   => false,
        'Key'      => false,
        'UploadId' => false
    );
}
