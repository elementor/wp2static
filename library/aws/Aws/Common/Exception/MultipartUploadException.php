<?php
namespace Aws\Common\Exception;
use Aws\Common\Model\MultipartUpload\TransferStateInterface;
class MultipartUploadException extends RuntimeException
{
    protected $state;
    public function __construct(TransferStateInterface $state, \Exception $exception = null)
    {
        parent::__construct(
            'An error was encountered while performing a multipart upload: ' . $exception->getMessage(),
            0,
            $exception
        );
        $this->state = $state;
    }
    public function getState()
    {
        return $this->state;
    }
}
