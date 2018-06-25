<?php
namespace Aws\Common\Exception;
interface AwsExceptionInterface
{
    public function getCode();
    public function getLine();
    public function getFile();
    public function getMessage();
    public function getPrevious();
    public function getTrace();
}
