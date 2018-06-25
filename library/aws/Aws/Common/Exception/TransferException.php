<?php
namespace Aws\Common\Exception;
use Guzzle\Http\Exception\CurlException;
class TransferException extends CurlException implements AwsExceptionInterface {}
