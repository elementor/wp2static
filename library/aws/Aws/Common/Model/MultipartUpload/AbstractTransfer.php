<?php
namespace Aws\Common\Model\MultipartUpload;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\MultipartUploadException;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\EntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Command\OperationCommand;
use Guzzle\Service\Resource\Model;
abstract class AbstractTransfer extends AbstractHasDispatcher implements TransferInterface
{
    const BEFORE_UPLOAD      = 'multipart_upload.before_upload';
    const AFTER_UPLOAD       = 'multipart_upload.after_upload';
    const BEFORE_PART_UPLOAD = 'multipart_upload.before_part_upload';
    const AFTER_PART_UPLOAD  = 'multipart_upload.after_part_upload';
    const AFTER_ABORT        = 'multipart_upload.after_abort';
    const AFTER_COMPLETE     = 'multipart_upload.after_complete';
    protected $client;
    protected $state;
    protected $source;
    protected $options;
    protected $partSize;
    protected $stopped = false;
    public function __construct(
        AwsClientInterface $client,
        TransferStateInterface $state,
        EntityBody $source,
        array $options = array()
    ) {
        $this->client  = $client;
        $this->state   = $state;
        $this->source  = $source;
        $this->options = $options;
        $this->init();
        $this->partSize = $this->calculatePartSize();
    }
    public function __invoke()
    {
        return $this->upload();
    }
    public static function getAllEvents()
    {
        return array(
            self::BEFORE_PART_UPLOAD,
            self::AFTER_UPLOAD,
            self::BEFORE_PART_UPLOAD,
            self::AFTER_PART_UPLOAD,
            self::AFTER_ABORT,
            self::AFTER_COMPLETE
        );
    }
    public function abort()
    {
        $command = $this->getAbortCommand();
        $result = $command->getResult();
        $this->state->setAborted(true);
        $this->stop();
        $this->dispatch(self::AFTER_ABORT, $this->getEventData($command));
        return $result;
    }
    public function stop()
    {
        $this->stopped = true;
        return $this->state;
    }
    public function getState()
    {
        return $this->state;
    }
    public function getOptions()
    {
        return $this->options;
    }
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }
    public function getSource()
    {
        return $this->source;
    }
    public function upload()
    {
        if ($this->state->isAborted()) {
            throw new RuntimeException('The transfer has been aborted and cannot be uploaded');
        }
        $this->stopped = false;
        $eventData = $this->getEventData();
        $this->dispatch(self::BEFORE_UPLOAD, $eventData);
        try {
            $this->transfer();
            $this->dispatch(self::AFTER_UPLOAD, $eventData);
            if ($this->stopped) {
                return null;
            } else {
                $result = $this->complete();
                $this->dispatch(self::AFTER_COMPLETE, $eventData);
            }
        } catch (\Exception $e) {
            throw new MultipartUploadException($this->state, $e);
        }
        return $result;
    }
    protected function getEventData(OperationCommand $command = null)
    {
        $data = array(
            'transfer'  => $this,
            'source'    => $this->source,
            'options'   => $this->options,
            'client'    => $this->client,
            'part_size' => $this->partSize,
            'state'     => $this->state
        );
        if ($command) {
            $data['command'] = $command;
        }
        return $data;
    }
    protected function init() {}
    abstract protected function calculatePartSize();
    abstract protected function complete();
    abstract protected function transfer();
    abstract protected function getAbortCommand();
}
