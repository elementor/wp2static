<?php
namespace Aws\S3\Sync;
interface FilenameConverterInterface
{
    public function convert($filename);
}
