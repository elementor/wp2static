<?php
namespace Aws\S3\Iterator;
class OpendirIterator implements \Iterator
{
    protected $dirHandle;
    protected $currentFile;
    protected $key = -1;
    protected $filePrefix;
    public function __construct($dirHandle, $filePrefix = '')
    {
        $this->filePrefix = $filePrefix;
        $this->dirHandle = $dirHandle;
        $this->next();
    }
    public function __destruct()
    {
        if ($this->dirHandle) {
            closedir($this->dirHandle);
        }
    }
    public function rewind()
    {
        $this->key = 0;
        rewinddir($this->dirHandle);
    }
    public function current()
    {
        return $this->currentFile;
    }
    public function next()
    {
        if ($file = readdir($this->dirHandle)) {
            $this->currentFile = new \SplFileInfo($this->filePrefix . $file);
        } else {
            $this->currentFile = false;
        }
        $this->key++;
    }
    public function key()
    {
        return $this->key;
    }
    public function valid()
    {
        return $this->currentFile !== false;
    }
}
