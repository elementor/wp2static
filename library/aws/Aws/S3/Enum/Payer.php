<?php
namespace Aws\S3\Enum;
use Aws\Common\Enum;
class Payer extends Enum
{
    const REQUESTER = 'Requester';
    const BUCKET_OWNER = 'BucketOwner';
}
