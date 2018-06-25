<?php
namespace Aws\S3\Sync;
use Aws\Common\Exception\RuntimeException;
use Aws\S3\ResumableDownload;
use Guzzle\Common\Event;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Command\CommandInterface;
class DownloadSyncBuilder extends AbstractSyncBuilder
{
    protected $resumable = false;
    protected $directory;
    protected $concurrency = 5;
    public function setDirectory($directory)
    {
        $this->directory = $directory;
        return $this;
    }
    public function allowResumableDownloads()
    {
        $this->resumable = true;
        return $this;
    }
    protected function specificBuild()
    {
        $sync = new DownloadSync(array(
            'client'           => $this->client,
            'bucket'           => $this->bucket,
            'iterator'         => $this->sourceIterator,
            'source_converter' => $this->sourceConverter,
            'target_converter' => $this->targetConverter,
            'concurrency'      => $this->concurrency,
            'resumable'        => $this->resumable,
            'directory'        => $this->directory
        ));
        return $sync;
    }
    protected function getTargetIterator()
    {
        if (!$this->directory) {
            throw new RuntimeException('A directory is required');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true)) {
            throw new RuntimeException('Unable to create root download directory: ' . $this->directory);
        }
        return $this->filterIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory))
        );
    }
    protected function getDefaultSourceConverter()
    {
        return new KeyConverter(
            "s3:
            $this->directory . DIRECTORY_SEPARATOR, $this->delimiter
        );
    }
    protected function getDefaultTargetConverter()
    {
        return new KeyConverter("s3:
    }
    protected function assertFileIteratorSet()
    {
        $this->sourceIterator = $this->sourceIterator ?: $this->createS3Iterator();
    }
    protected function addDebugListener(AbstractSync $sync, $resource)
    {
        $sync->getEventDispatcher()->addListener(UploadSync::BEFORE_TRANSFER, function (Event $e) use ($resource) {
            if ($e['command'] instanceof CommandInterface) {
                $from = $e['command']['Bucket'] . '/' . $e['command']['Key'];
                $to = $e['command']['SaveAs'] instanceof EntityBodyInterface
                    ? $e['command']['SaveAs']->getUri()
                    : $e['command']['SaveAs'];
                fwrite($resource, "Downloading {$from} -> {$to}\n");
            } elseif ($e['command'] instanceof ResumableDownload) {
                $from = $e['command']->getBucket() . '/' . $e['command']->getKey();
                $to = $e['command']->getFilename();
                fwrite($resource, "Resuming {$from} -> {$to}\n");
            }
        });
    }
}
