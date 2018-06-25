<?php
namespace Monolog\Processor;
abstract class MemoryProcessor
{
    protected $realUsage;
    protected $useFormatting;
    public function __construct($realUsage = true, $useFormatting = true)
    {
        $this->realUsage = (boolean) $realUsage;
        $this->useFormatting = (boolean) $useFormatting;
    }
    protected function formatBytes($bytes)
    {
        $bytes = (int) $bytes;
        if (!$this->useFormatting) {
            return $bytes;
        }
        if ($bytes > 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2).' MB';
        } elseif ($bytes > 1024) {
            return round($bytes / 1024, 2).' KB';
        }
        return $bytes . ' B';
    }
}
