<?php
namespace Aws\S3\Sync;
use Aws\Common\Exception\RuntimeException;
use Aws\S3\ResumableDownload;
class DownloadSync extends AbstractSync
{
    protected function createTransferAction(\SplFileInfo $file)
    {
        $sourceFilename = $file->getPathname();
        list($bucket, $key) = explode('/', substr($sourceFilename, 5), 2);
        $filename = $this->options['source_converter']->convert($sourceFilename);
        $this->createDirectory($filename);
        if (is_dir($filename)) {
            return false;
        }
        if (file_exists($filename) && $this->options['resumable']) {
            return new ResumableDownload($this->options['client'], $bucket, $key, $filename);
        }
        return $this->options['client']->getCommand('GetObject', array(
            'Bucket' => $bucket,
            'Key'    => $key,
            'SaveAs' => $filename
        ));
    }
    protected function createDirectory($filename)
    {
        $directory = dirname($filename);
        if (is_file($directory) && filesize($directory) == 0) {
            unlink($directory);
        }
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            $errors = error_get_last();
            throw new RuntimeException('Could not create directory: ' . $directory . ' - ' . $errors['message']);
        }
    }
    protected function filterCommands(array $commands)
    {
        $dirs = array();
        foreach ($commands as $command) {
            $parts = array_values(array_filter(explode('/', $command['SaveAs'])));
            for ($i = 0, $total = count($parts); $i < $total; $i++) {
                $dir = '';
                for ($j = 0; $j < $i; $j++) {
                    $dir .= '/' . $parts[$j];
                }
                if ($dir && !in_array($dir, $dirs)) {
                    $dirs[] = $dir;
                }
            }
        }
        return array_filter($commands, function ($command) use ($dirs) {
            return !in_array($command['SaveAs'], $dirs);
        });
    }
    protected function transferCommands(array $commands)
    {
        parent::transferCommands($this->filterCommands($commands));
    }
}
