<?php
/**
 * UploadPartInterface
 *
 * @package WP2Static
 */

namespace Aws\Common\Model\MultipartUpload;
interface UploadPartInterface extends \Serializable
{
    public static function fromArray($data);
    public function getPartNumber();
    public function toArray();
}
