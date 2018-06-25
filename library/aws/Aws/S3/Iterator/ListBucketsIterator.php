<?php
namespace Aws\S3\Iterator;
use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Service\Resource\Model;
class ListBucketsIterator extends AwsResourceIterator
{
    protected function handleResults(Model $result)
    {
        $buckets = $result->get('Buckets') ?: array();
        if ($this->get('names_only')) {
            foreach ($buckets as &$bucket) {
                $bucket = $bucket['Name'];
            }
        }
        return $buckets;
    }
}
