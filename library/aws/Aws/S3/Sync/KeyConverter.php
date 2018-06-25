<?php
namespace Aws\S3\Sync;
class KeyConverter implements FilenameConverterInterface
{
    protected $delimiter;
    protected $prefix;
    protected $baseDir;
    public function __construct($baseDir = '', $prefix = '', $delimiter = '/')
    {
        $this->baseDir = (string) $baseDir;
        $this->prefix = $prefix;
        $this->delimiter = $delimiter;
    }
    public function convert($filename)
    {
        $key = $filename;
        if ($this->baseDir && (false !== $pos = strpos($filename, $this->baseDir))) {
            $key = substr_replace($key, '', $pos, strlen($this->baseDir));
        }
        $key = str_replace('/', $this->delimiter, str_replace('\\', '/', $key));
        $delim = preg_quote($this->delimiter);
        $key = preg_replace(
            "#(?<!:){$delim}{$delim}#",
            $this->delimiter,
            $this->prefix . $key
        );
        return $key;
    }
}
