<?php
namespace Aws\Common\Model\MultipartUpload;
use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Service\Resource\Model;
interface TransferInterface extends HasDispatcherInterface
{
    public function upload();
    public function abort();
    public function getState();
    public function stop();
    public function setOption($option, $value);
}
