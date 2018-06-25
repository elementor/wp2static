<?php
namespace Aws\S3\Enum;
use Aws\Common\Enum;
class StorageClass extends Enum
{
    const STANDARD = 'STANDARD';
    const REDUCED_REDUNDANCY = 'REDUCED_REDUNDANCY';
}
