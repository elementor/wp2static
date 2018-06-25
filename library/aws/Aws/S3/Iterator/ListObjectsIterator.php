<?php
namespace Aws\S3\Iterator;
use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;
class ListObjectsIterator extends AwsResourceIterator
{
    protected function handleResults(Model $result)
    {
        $objects = $result->get('Contents') ?: array();
        $numObjects = count($objects);
        $lastKey = $numObjects ? $objects[$numObjects - 1]['Key'] : false;
        if ($lastKey && !$result->hasKey($this->get('output_token'))) {
            $result->set($this->get('output_token'), $lastKey);
        }
        $getName = function ($object) {
            return isset($object['Key']) ? $object['Key'] : $object['Prefix'];
        };
        if ($this->get('return_prefixes') && $result->hasKey('CommonPrefixes')) {
            $objects = array_merge($objects, $result->get('CommonPrefixes'));
            if ($this->get('sort_results') && $lastKey && $objects) {
                usort($objects, function ($object1, $object2) use ($getName) {
                    return strcmp($getName($object1), $getName($object2));
                });
            }
        }
        if ($this->get('names_only')) {
            $objects = array_map($getName, $objects);
        }
        return $objects;
    }
}
