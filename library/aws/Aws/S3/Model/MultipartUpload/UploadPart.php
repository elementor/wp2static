<?php
namespace Aws\S3\Model\MultipartUpload;
use Aws\Common\Model\MultipartUpload\AbstractUploadPart;
class UploadPart extends AbstractUploadPart
{
    protected static $keyMap = array(
        'PartNumber'   => 'partNumber',
        'ETag'         => 'eTag',
        'LastModified' => 'lastModified',
        'Size'         => 'size'
    );
    protected $eTag;
    protected $lastModified;
    protected $size;
    public function getETag()
    {
        return $this->eTag;
    }
    public function getLastModified()
    {
        return $this->lastModified;
    }
    public function getSize()
    {
        return $this->size;
    }
}
