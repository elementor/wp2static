<?php
namespace Aws\S3\Enum;
use Aws\Common\Enum;
class GranteeType extends Enum
{
    const USER = 'CanonicalUser';
    const EMAIL = 'AmazonCustomerByEmail';
    const GROUP = 'Group';
}
