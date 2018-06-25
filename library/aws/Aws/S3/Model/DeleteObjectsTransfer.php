<?php
namespace Aws\S3\Model;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\OverflowException;
use Aws\Common\Enum\UaString as Ua;
use Aws\S3\Exception\InvalidArgumentException;
use Aws\S3\Exception\DeleteMultipleObjectsException;
use Guzzle\Batch\BatchTransferInterface;
use Guzzle\Service\Command\CommandInterface;
class DeleteObjectsTransfer implements BatchTransferInterface
{
    protected $client;
    protected $bucket;
    protected $mfa;
    public function __construct(AwsClientInterface $client, $bucket, $mfa = null)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->mfa = $mfa;
    }
    public function setMfa($token)
    {
        $this->mfa = $token;
        return $this;
    }
    public function transfer(array $batch)
    {
        if (empty($batch)) {
            return;
        }
        if (count($batch) > 1000) {
            throw new OverflowException('Batches should be divided into chunks of no larger than 1000 keys');
        }
        $del = array();
        $command = $this->client->getCommand('DeleteObjects', array(
            'Bucket'   => $this->bucket,
            Ua::OPTION => Ua::BATCH
        ));
        if ($this->mfa) {
            $command->getRequestHeaders()->set('x-amz-mfa', $this->mfa);
        }
        foreach ($batch as $object) {
            if (!is_array($object) || !isset($object['Key'])) {
                throw new InvalidArgumentException('Invalid batch item encountered: ' . var_export($batch, true));
            }
            $del[] = array(
                'Key'       => $object['Key'],
                'VersionId' => isset($object['VersionId']) ? $object['VersionId'] : null
            );
        }
        $command['Objects'] = $del;
        $command->execute();
        $this->processResponse($command);
    }
    protected function processResponse(CommandInterface $command)
    {
        $result = $command->getResult();
        if (!empty($result['Errors'])) {
            $errors = $result['Errors'];
            throw new DeleteMultipleObjectsException($errors);
        }
    }
}
