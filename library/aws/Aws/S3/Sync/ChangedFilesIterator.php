<?php
namespace Aws\S3\Sync;
class ChangedFilesIterator extends \FilterIterator
{
    protected $sourceIterator;
    protected $targetIterator;
    protected $sourceConverter;
    protected $targetConverter;
    protected $cache = array();
    public function __construct(
        \Iterator $sourceIterator,
        \Iterator $targetIterator,
        FilenameConverterInterface $sourceConverter,
        FilenameConverterInterface $targetConverter
    ) {
        $this->targetIterator = $targetIterator;
        $this->sourceConverter = $sourceConverter;
        $this->targetConverter = $targetConverter;
        parent::__construct($sourceIterator);
    }
    public function accept()
    {
        $current = $this->current();
        $key = $this->sourceConverter->convert($this->normalize($current));
        if (!($data = $this->getTargetData($key))) {
            return true;
        }
        return $current->getSize() != $data[0] || $current->getMTime() > $data[1];
    }
    public function getUnmatched()
    {
        return array_keys($this->cache);
    }
    protected function getTargetData($key)
    {
        $key = $this->cleanKey($key);
        if (isset($this->cache[$key])) {
            $result = $this->cache[$key];
            unset($this->cache[$key]);
            return $result;
        }
        $it = $this->targetIterator;
        while ($it->valid()) {
            $value = $it->current();
            $data = array($value->getSize(), $value->getMTime());
            $filename = $this->targetConverter->convert($this->normalize($value));
            $filename = $this->cleanKey($filename);
            if ($filename == $key) {
                return $data;
            }
            $this->cache[$filename] = $data;
            $it->next();
        }
        return false;
    }
    private function normalize($current)
    {
        $asString = (string) $current;
        return strpos($asString, 's3:
            ? $asString
            : $current->getRealPath();
    }
    private function cleanKey($key)
    {
        return ltrim($key, '/');
    }
}
