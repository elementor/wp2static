<?php
namespace Aws\S3\Sync;
use Aws\S3\S3Client;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Collection;
use Guzzle\Iterator\ChunkedIterator;
use Guzzle\Service\Command\CommandInterface;
abstract class AbstractSync extends AbstractHasDispatcher
{
    const BEFORE_TRANSFER = 's3.sync.before_transfer';
    const AFTER_TRANSFER = 's3.sync.after_transfer';
    protected $options;
    public function __construct(array $options)
    {
        $this->options = Collection::fromConfig(
            $options,
            array('concurrency' => 10),
            array('client', 'bucket', 'iterator', 'source_converter')
        );
        $this->init();
    }
    public static function getAllEvents()
    {
        return array(self::BEFORE_TRANSFER, self::AFTER_TRANSFER);
    }
    public function transfer()
    {
        $iterator = new ChunkedIterator($this->options['iterator'], $this->options['concurrency']);
        foreach ($iterator as $files) {
            $this->transferFiles($files);
        }
    }
    abstract protected function createTransferAction(\SplFileInfo $file);
    protected function init() {}
    protected function transferFiles(array $files)
    {
        $event = array('sync' => $this, 'client' => $this->options['client']);
        $commands = array();
        foreach ($files as $file) {
            if ($action = $this->createTransferAction($file)) {
                $event = array('command' => $action, 'file' => $file) + $event;
                $this->dispatch(self::BEFORE_TRANSFER, $event);
                if ($action instanceof CommandInterface) {
                    $commands[] = $action;
                } elseif (is_callable($action)) {
                    $action();
                    $this->dispatch(self::AFTER_TRANSFER, $event);
                }
            }
        }
        $this->transferCommands($commands);
    }
    protected function transferCommands(array $commands)
    {
        if ($commands) {
            $this->options['client']->execute($commands);
            $event = array('sync' => $this, 'client' => $this->options['client']);
            foreach ($commands as $command) {
                $event['command'] = $command;
                $this->dispatch(self::AFTER_TRANSFER, $event);
            }
        }
    }
}
