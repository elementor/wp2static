<?php
namespace Aws\Common\Enum;
use Aws\Common\Enum;
class DateFormat extends Enum
{
    const ISO8601    = 'Ymd\THis\Z';
    const ISO8601_S3 = 'Y-m-d\TH:i:s\Z';
    const RFC1123    = 'D, d M Y H:i:s \G\M\T';
    const RFC2822    = \DateTime::RFC2822;
    const SHORT      = 'Ymd';
}
