<?php
namespace Aws\Exception;
use Aws\Multipart\UploadState;
class MultipartUploadException extends \RuntimeException
{
    private $state;
    public function __construct(UploadState $state, $prev = null) {
        $msg = 'An exception occurred while performing a multipart upload';
        if (is_array($prev)) {
            $msg = strtr($msg, ['performing' => 'uploading parts to']);
            $msg .= ". The following parts had errors:\n";
            foreach ($prev as $part => $error) {
                $msg .= "- Part {$part}: " . $error->getMessage(). "\n";
            }
        } elseif ($prev instanceof AwsException) {
            switch ($prev->getCommand()->getName()) {
                case 'CreateMultipartUpload':
                case 'InitiateMultipartUpload':
                    $action = 'initiating';
                    break;
                case 'CompleteMultipartUpload':
                    $action = 'completing';
                    break;
            }
            if (isset($action)) {
                $msg = strtr($msg, ['performing' => $action]);
            }
            $msg .= ": {$prev->getMessage()}";
        }
        if (!$prev instanceof \Exception) {
            $prev = null;
        }
        parent::__construct($msg, 0, $prev);
        $this->state = $state;
    }
    public function getState()
    {
        return $this->state;
    }
}
