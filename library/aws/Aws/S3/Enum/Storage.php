<?php
namespace Aws\S3\Enum;
use Aws\Common\Enum;
class Storage extends Enum
{
    const STANDARD = 'STANDARD';
    const REDUCED  = 'REDUCED_REDUNDANCY';
    const GLACIER = 'GLACIER';
}
