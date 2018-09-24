<?php
/**
 * UploadIdInterface
 *
 * @package WP2Static
 */

namespace Aws\Common\Model\MultipartUpload;
interface UploadIdInterface extends \Serializable
{
    public static function fromParams($data);
    public function toParams();
}
