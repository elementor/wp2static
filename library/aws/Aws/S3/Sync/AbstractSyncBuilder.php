<?php
namespace Aws\S3\Sync;
use Aws\Common\Exception\RuntimeException;
use Aws\Common\Exception\UnexpectedValueException;
use Aws\S3\S3Client;
use Aws\S3\Iterator\OpendirIterator;
use Guzzle\Common\Event;
use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Iterator\FilterIterator;
use Guzzle\Service\Command\CommandInterface;
abstract class AbstractSyncBuilder
{
    protected $sourceIterator;
    protected $client;
    protected $bucket;
    protected $concurrency = 10;
    protected $params = array();
    protected $sourceConverter;
    protected $targetConverter;
    protected $keyPrefix = '';
    protected $delimiter = '/';
    protected $baseDir;
    protected $forcing = false;
    protected $debug;
    public static function getInstance()
    {
        return new static();
    }
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }
    public function setClient(S3Client $client)
    {
        $this->client = $client;
        return $this;
    }
    public function setSourceIterator(\Iterator $iterator)
    {
        $this->sourceIterator = $iterator;
        return $this;
    }
    public function setSourceFilenameConverter(FilenameConverterInterface $converter)
    {
        $this->sourceConverter = $converter;
        return $this;
    }
    public function setTargetFilenameConverter(FilenameConverterInterface $converter)
    {
        $this->targetConverter = $converter;
        return $this;
    }
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
        return $this;
    }
    public function setKeyPrefix($keyPrefix)
    {
        $this->keyPrefix = ltrim($keyPrefix, '/');
        return $this;
    }
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }
    public function setOperationParams(array $params)
    {
        $this->params = $params;
        return $this;
    }
    public function setConcurrency($concurrency)
    {
        $this->concurrency = $concurrency;
        return $this;
    }
    public function force($force = false)
    {
        $this->forcing = (bool) $force;
        return $this;
    }
    public function enableDebugOutput($enabledOrResource = true)
    {
        $this->debug = $enabledOrResource;
        return $this;
    }
    public function addRegexFilter($search)
    {
        $this->assertFileIteratorSet();
        $this->sourceIterator = new FilterIterator($this->sourceIterator, function ($i) use ($search) {
            return !preg_match($search, (string) $i);
        });
        $this->sourceIterator->rewind();
        return $this;
    }
    public function build()
    {
        $this->validateRequirements();
        $this->sourceConverter = $this->sourceConverter ?: $this->getDefaultSourceConverter();
        $this->targetConverter = $this->targetConverter ?: $this->getDefaultTargetConverter();
        if (!$this->forcing) {
            $this->sourceIterator->rewind();
            $this->sourceIterator = new ChangedFilesIterator(
                new \NoRewindIterator($this->sourceIterator),
                $this->getTargetIterator(),
                $this->sourceConverter,
                $this->targetConverter
            );
            $this->sourceIterator->rewind();
        }
        $sync = $this->specificBuild();
        if ($this->params) {
            $this->addCustomParamListener($sync);
        }
        if ($this->debug) {
            $this->addDebugListener($sync, is_bool($this->debug) ? STDOUT : $this->debug);
        }
        return $sync;
    }
    abstract protected function specificBuild();
    abstract protected function getTargetIterator();
    abstract protected function getDefaultSourceConverter();
    abstract protected function getDefaultTargetConverter();
    abstract protected function addDebugListener(AbstractSync $sync, $resource);
    protected function validateRequirements()
    {
        if (!$this->client) {
            throw new RuntimeException('No client was provided');
        }
        if (!$this->bucket) {
            throw new RuntimeException('No bucket was provided');
        }
        $this->assertFileIteratorSet();
    }
    protected function assertFileIteratorSet()
    {
        if (!isset($this->sourceIterator)) {
            throw new RuntimeException('A source file iterator must be specified');
        }
    }
    protected function filterIterator(\Iterator $iterator)
    {
        $f = new FilterIterator($iterator, function ($i) {
            if (!$i instanceof \SplFileInfo) {
                throw new UnexpectedValueException('All iterators for UploadSync must return SplFileInfo objects');
            }
            return $i->isFile();
        });
        $f->rewind();
        return $f;
    }
    protected function addCustomParamListener(HasDispatcherInterface $sync)
    {
        $params = $this->params;
        $sync->getEventDispatcher()->addListener(
            UploadSync::BEFORE_TRANSFER,
            function (Event $e) use ($params) {
                if ($e['command'] instanceof CommandInterface) {
                    $e['command']->overwriteWith($params);
                }
            }
        );
    }
    protected function createS3Iterator()
    {
        $this->client->registerStreamWrapper();
        $dir = "s3:
        if ($this->keyPrefix) {
            $dir .= '/' . ltrim($this->keyPrefix, '/ ');
        }
        $dh = opendir($dir, stream_context_create(array(
            's3' => array(
                'delimiter'  => '',
                'listFilter' => function ($obj) {
                    return !isset($obj['StorageClass']) ||
                        $obj['StorageClass'] != 'GLACIER';
                }
            )
        )));
        if (!$this->keyPrefix) {
            $dir .= '/';
        }
        return $this->filterIterator(new \NoRewindIterator(new OpendirIterator($dh, $dir)));
    }
}
