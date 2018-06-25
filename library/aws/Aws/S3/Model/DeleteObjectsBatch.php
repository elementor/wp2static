<?php
namespace Aws\S3\Model;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\InvalidArgumentException;
use Guzzle\Service\Command\AbstractCommand;
use Guzzle\Batch\BatchBuilder;
use Guzzle\Batch\BatchSizeDivisor;
use Guzzle\Batch\AbstractBatchDecorator;
class DeleteObjectsBatch extends AbstractBatchDecorator
{
    public static function factory(AwsClientInterface $client, $bucket, $mfa = null)
    {
        $batch = BatchBuilder::factory()
            ->createBatchesWith(new BatchSizeDivisor(1000))
            ->transferWith(new DeleteObjectsTransfer($client, $bucket, $mfa))
            ->build();
        return new static($batch);
    }
    public function addKey($key, $versionId = null)
    {
        return $this->add(array(
            'Key'       => $key,
            'VersionId' => $versionId
        ));
    }
    public function add($item)
    {
        if ($item instanceof AbstractCommand && $item->getName() == 'DeleteObject') {
            $item = array(
                'Key'       => $item['Key'],
                'VersionId' => $item['VersionId']
            );
        }
        if (!is_array($item) || (!isset($item['Key']))) {
            throw new InvalidArgumentException('Item must be a DeleteObject command or array containing a Key and VersionId key.');
        }
        return parent::add($item);
    }
}
