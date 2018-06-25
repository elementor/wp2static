<?php
namespace Aws\S3\Enum;
use Aws\Common\Enum;
class Permission extends Enum
{
    const FULL_CONTROL = 'FULL_CONTROL';
    const WRITE = 'WRITE';
    const WRITE_ACP = 'WRITE_ACP';
    const READ = 'READ';
    const READ_ACP = 'READ_ACP';
}
