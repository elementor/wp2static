<?php
namespace Aws\S3\Crypto;
use \Aws\Crypto\MetadataStrategyInterface;
use \Aws\Crypto\MetadataEnvelope;
use \Aws\S3\S3Client;
class InstructionFileMetadataStrategy implements MetadataStrategyInterface
{
    const DEFAULT_FILE_SUFFIX = '.instruction';
    private $client;
    private $suffix;
    public function __construct(S3Client $client, $suffix = null)
    {
        $this->suffix = empty($suffix)
            ? self::DEFAULT_FILE_SUFFIX
            : $suffix;
        $this->client = $client;
    }
    public function save(MetadataEnvelope $envelope, array $args)
    {
        $this->client->putObject([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'] . $this->suffix,
            'Body' => json_encode($envelope)
        ]);
        return $args;
    }
    public function load(array $args)
    {
        $result = $this->client->getObject([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'] . $this->suffix
        ]);
        $metadataHeaders = json_decode($result['Body'], true);
        $envelope = new MetadataEnvelope();
        $constantValues = MetadataEnvelope::getConstantValues();
        foreach ($constantValues as $constant) {
            if (!empty($metadataHeaders[$constant])) {
                $envelope[$constant] = $metadataHeaders[$constant];
            }
        }
        return $envelope;
    }
}
