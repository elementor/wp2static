<?php
namespace Aws\S3\Iterator;
use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;
class ListObjectVersionsIterator extends AwsResourceIterator
{
    protected function handleResults(Model $result)
    {
        $versions = $result->get('Versions') ?: array();
        $deleteMarkers = $result->get('DeleteMarkers') ?: array();
        $versions = array_merge($versions, $deleteMarkers);
        if ($this->get('return_prefixes') && $result->hasKey('CommonPrefixes')) {
            $versions = array_merge($versions, $result->get('CommonPrefixes'));
        }
        return $versions;
    }
}
