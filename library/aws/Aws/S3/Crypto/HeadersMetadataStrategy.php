<?php
namespace Aws\S3\Crypto;
use \Aws\Crypto\MetadataStrategyInterface;
use \Aws\Crypto\MetadataEnvelope;
class HeadersMetadataStrategy implements MetadataStrategyInterface
{
    public function save(MetadataEnvelope $envelope, array $args)
    {
        foreach ($envelope as $header=>$value) {
            $args['Metadata'][$header] = $value;
        }
        return $args;
    }
    public function load(array $args)
    {
        $envelope = new MetadataEnvelope();
        $constantValues = MetadataEnvelope::getConstantValues();
        foreach ($constantValues as $constant) {
            if (!empty($args['Metadata'][$constant])) {
                $envelope[$constant] = $args['Metadata'][$constant];
            }
        }
        return $envelope;
    }
}
