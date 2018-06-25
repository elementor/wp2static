<?php
namespace Monolog\Handler;
use Monolog\Logger;
class RotatingFileHandler extends StreamHandler
{
    const FILE_PER_DAY = 'Y-m-d';
    const FILE_PER_MONTH = 'Y-m';
    const FILE_PER_YEAR = 'Y';
    protected $filename;
    protected $maxFiles;
    protected $mustRotate;
    protected $nextRotation;
    protected $filenameFormat;
    protected $dateFormat;
    public function __construct($filename, $maxFiles = 0, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        $this->filename = $filename;
        $this->maxFiles = (int) $maxFiles;
        $this->nextRotation = new \DateTime('tomorrow');
        $this->filenameFormat = '{filename}-{date}';
        $this->dateFormat = 'Y-m-d';
        parent::__construct($this->getTimedFilename(), $level, $bubble, $filePermission, $useLocking);
    }
    public function close()
    {
        parent::close();
        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }
    public function setFilenameFormat($filenameFormat, $dateFormat)
    {
        if (!in_array($dateFormat, array(self::FILE_PER_DAY, self::FILE_PER_MONTH, self::FILE_PER_YEAR))) {
            trigger_error(
                'Invalid date format - format should be one of '.
                'RotatingFileHandler::FILE_PER_DAY, RotatingFileHandler::FILE_PER_MONTH '.
                'or RotatingFileHandler::FILE_PER_YEAR.',
                E_USER_DEPRECATED
            );
        }
        if (substr_count($filenameFormat, '{date}') === 0) {
            trigger_error(
                'Invalid filename format - format should contain at least `{date}`, because otherwise rotating is impossible.',
                E_USER_DEPRECATED
            );
        }
        $this->filenameFormat = $filenameFormat;
        $this->dateFormat = $dateFormat;
        $this->url = $this->getTimedFilename();
        $this->close();
    }
    protected function write(array $record)
    {
        if (null === $this->mustRotate) {
            $this->mustRotate = !file_exists($this->url);
        }
        if ($this->nextRotation < $record['datetime']) {
            $this->mustRotate = true;
            $this->close();
        }
        parent::write($record);
    }
    protected function rotate()
    {
        $this->url = $this->getTimedFilename();
        $this->nextRotation = new \DateTime('tomorrow');
        if (0 === $this->maxFiles) {
            return;
        }
        $logFiles = glob($this->getGlobPattern());
        if ($this->maxFiles >= count($logFiles)) {
            return;
        }
        usort($logFiles, function ($a, $b) {
            return strcmp($b, $a);
        });
        foreach (array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                set_error_handler(function ($errno, $errstr, $errfile, $errline) {});
                unlink($file);
                restore_error_handler();
            }
        }
        $this->mustRotate = false;
    }
    protected function getTimedFilename()
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(
            array('{filename}', '{date}'),
            array($fileInfo['filename'], date($this->dateFormat)),
            $fileInfo['dirname'] . '/' . $this->filenameFormat
        );
        if (!empty($fileInfo['extension'])) {
            $timedFilename .= '.'.$fileInfo['extension'];
        }
        return $timedFilename;
    }
    protected function getGlobPattern()
    {
        $fileInfo = pathinfo($this->filename);
        $glob = str_replace(
            array('{filename}', '{date}'),
            array($fileInfo['filename'], '*'),
            $fileInfo['dirname'] . '/' . $this->filenameFormat
        );
        if (!empty($fileInfo['extension'])) {
            $glob .= '.'.$fileInfo['extension'];
        }
        return $glob;
    }
}
