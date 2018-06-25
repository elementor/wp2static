<?php
namespace Aws\S3\Crypto;
use Aws\Crypto\AbstractCryptoClient;
use Aws\Crypto\EncryptionTrait;
use Aws\Crypto\MetadataEnvelope;
use Aws\Crypto\Cipher\CipherBuilderTrait;
use Aws\S3\MultipartUploader;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Promise;
class S3EncryptionMultipartUploader extends MultipartUploader
{
    use EncryptionTrait, CipherBuilderTrait, CryptoParamsTrait;
    public static function isSupportedCipher($cipherName)
    {
        return in_array($cipherName, AbstractCryptoClient::$supportedCiphers);
    }
    private $provider;
    private $instructionFileSuffix;
    private $strategy;
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        $this->client = $client;
        $config['params'] = [];
        if (!empty($config['bucket'])) {
            $config['params']['Bucket'] = $config['bucket'];
        }
        if (!empty($config['key'])) {
            $config['params']['Key'] = $config['key'];
        }
        $this->provider = $this->getMaterialsProvider($config);
        unset($config['@MaterialsProvider']);
        $this->instructionFileSuffix = $this->getInstructionFileSuffix($config);
        unset($config['@InstructionFileSuffix']);
        $this->strategy = $this->getMetadataStrategy(
            $config,
            $this->instructionFileSuffix
        );
        if ($this->strategy === null) {
            $this->strategy = self::getDefaultStrategy();
        }
        unset($config['@MetadataStrategy']);
        $config['prepare_data_source'] = $this->getEncryptingDataPreparer();
        parent::__construct($client, $source, $config);
    }
    private static function getDefaultStrategy()
    {
        return new HeadersMetadataStrategy();
    }
    private function getEncryptingDataPreparer()
    {
        return function() {
            $envelope = new MetadataEnvelope();
            list($this->source, $params) = Promise\promise_for($this->encrypt(
                $this->source,
                $this->config['@cipheroptions'] ?: [],
                $this->provider,
                $envelope
            ))->then(
                function ($bodyStream) use ($envelope) {
                    $params = $this->strategy->save(
                        $envelope,
                        $this->config['params']
                    );
                    return [$bodyStream, $params];
                }
            )->wait();
            $this->source->rewind();
            $this->config['params'] = $params;
        };
    }
}
