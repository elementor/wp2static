<?php
namespace Aws\S3\Iterator;
use Guzzle\Service\Resource\Model;
use Aws\Common\Iterator\AwsResourceIterator;
class ListMultipartUploadsIterator extends AwsResourceIterator
{
    protected function handleResults(Model $result)
    {
        $uploads = $result->get('Uploads') ?: array();
        if ($this->get('return_prefixes') && $result->hasKey('CommonPrefixes')) {
            $uploads = array_merge($uploads, $result->get('CommonPrefixes'));
        }
        return $uploads;
    }
}
