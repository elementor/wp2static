<?php
namespace Aws\S3\Sync;
use FilesystemIterator as FI;
use Aws\Common\Model\MultipartUpload\AbstractTransfer;
use Aws\S3\Model\Acp;
use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;
class UploadSyncBuilder extends AbstractSyncBuilder
{
    protected $acp = 'private';
    protected $multipartUploadSize;
    public function uploadFromDirectory($path)
    {
        $this->baseDir = realpath($path);
        $this->sourceIterator = $this->filterIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $path,
            FI::SKIP_DOTS | FI::UNIX_PATHS | FI::FOLLOW_SYMLINKS
        )));
        return $this;
    }
    public function uploadFromGlob($glob)
    {
        $this->sourceIterator = $this->filterIterator(
            new \GlobIterator($glob, FI::SKIP_DOTS | FI::UNIX_PATHS | FI::FOLLOW_SYMLINKS)
        );
        return $this;
    }
    public function setAcl($acl)
    {
        $this->acp = $acl;
        return $this;
    }
    public function setAcp(Acp $acp)
    {
        $this->acp = $acp;
        return $this;
    }
    public function setMultipartUploadSize($size)
    {
        $this->multipartUploadSize = $size;
        return $this;
    }
    protected function specificBuild()
    {
        $sync = new UploadSync(array(
            'client' => $this->client,
            'bucket' => $this->bucket,
            'iterator' => $this->sourceIterator,
            'source_converter' => $this->sourceConverter,
            'target_converter' => $this->targetConverter,
            'concurrency' => $this->concurrency,
            'multipart_upload_size' => $this->multipartUploadSize,
            'acl' => $this->acp
        ));
        return $sync;
    }
    protected function addCustomParamListener(HasDispatcherInterface $sync)
    {
        parent::addCustomParamListener($sync);
        $params = $this->params;
        $sync->getEventDispatcher()->addListener(
            UploadSync::BEFORE_MULTIPART_BUILD,
            function (Event $e) use ($params) {
                foreach ($params as $k => $v) {
                    $e['builder']->setOption($k, $v);
                }
            }
        );
    }
    protected function getTargetIterator()
    {
        return $this->createS3Iterator();
    }
    protected function getDefaultSourceConverter()
    {
        return new KeyConverter($this->baseDir, $this->keyPrefix . $this->delimiter, $this->delimiter);
    }
    protected function getDefaultTargetConverter()
    {
        return new KeyConverter('s3:
    }
    protected function addDebugListener(AbstractSync $sync, $resource)
    {
        $sync->getEventDispatcher()->addListener(UploadSync::BEFORE_TRANSFER, function (Event $e) use ($resource) {
            $c = $e['command'];
            if ($c instanceof CommandInterface) {
                $uri = $c['Body']->getUri();
                $size = $c['Body']->getSize();
                fwrite($resource, "Uploading {$uri} -> {$c['Key']} ({$size} bytes)\n");
                return;
            }
            $body = $c->getSource();
            $totalSize = $body->getSize();
            $progress = 0;
            fwrite($resource, "Beginning multipart upload: " . $body->getUri() . ' -> ');
            fwrite($resource, $c->getState()->getFromId('Key') . " ({$totalSize} bytes)\n");
            $c->getEventDispatcher()->addListener(
                AbstractTransfer::BEFORE_PART_UPLOAD,
                function ($e) use (&$progress, $totalSize, $resource) {
                    $command = $e['command'];
                    $size = $command['Body']->getContentLength();
                    $percentage = number_format(($progress / $totalSize) * 100, 2);
                    fwrite($resource, "- Part {$command['PartNumber']} ({$size} bytes, {$percentage}%)\n");
                    $progress += $size;
                }
            );
        });
    }
}
