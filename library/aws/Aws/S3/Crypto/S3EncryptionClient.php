<?php
namespace Aws\S3\Crypto;
use Aws\HashingStream;
use Aws\PhpHash;
use Aws\Crypto\AbstractCryptoClient;
use Aws\Crypto\EncryptionTrait;
use Aws\Crypto\DecryptionTrait;
use Aws\Crypto\MetadataEnvelope;
use Aws\Crypto\MaterialsProvider;
use Aws\Crypto\Cipher\CipherBuilderTrait;
use Aws\S3\S3Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
class S3EncryptionClient extends AbstractCryptoClient
{
    use EncryptionTrait, DecryptionTrait, CipherBuilderTrait, CryptoParamsTrait;
    private $client;
    private $instructionFileSuffix;
    public function __construct(
        S3Client $client,
        $instructionFileSuffix = null
    ) {
        $this->client = $client;
        $this->instructionFileSuffix = $instructionFileSuffix;
    }
    private static function getDefaultStrategy()
    {
        return new HeadersMetadataStrategy();
    }
    public function putObjectAsync(array $args)
    {
        $provider = $this->getMaterialsProvider($args);
        unset($args['@MaterialsProvider']);
        $instructionFileSuffix = $this->getInstructionFileSuffix($args);
        unset($args['@InstructionFileSuffix']);
        $strategy = $this->getMetadataStrategy($args, $instructionFileSuffix);
        unset($args['@MetadataStrategy']);
        $envelope = new MetadataEnvelope();
        return Promise\promise_for($this->encrypt(
            Psr7\stream_for($args['Body']),
            $args['@CipherOptions'] ?: [],
            $provider,
            $envelope
        ))->then(
            function ($encryptedBodyStream) use ($args) {
                $hash = new PhpHash('sha256');
                $hashingEncryptedBodyStream = new HashingStream(
                    $encryptedBodyStream,
                    $hash,
                    self::getContentShaDecorator($args)
                );
                return [$hashingEncryptedBodyStream, $args];
            }
        )->then(
            function ($putObjectContents) use ($strategy, $envelope) {
                list($bodyStream, $args) = $putObjectContents;
                if ($strategy === null) {
                    $strategy = self::getDefaultStrategy();
                }
                $updatedArgs = $strategy->save($envelope, $args);
                $updatedArgs['Body'] = $bodyStream;
                return $updatedArgs;
            }
        )->then(
            function ($args) {
                unset($args['@CipherOptions']);
                return $this->client->putObjectAsync($args);
            }
        );
    }
    private static function getContentShaDecorator(&$args)
    {
        return function ($hash) use (&$args) {
            $args['ContentSHA256'] = bin2hex($hash);
        };
    }
    public function putObject(array $args)
    {
        return $this->putObjectAsync($args)->wait();
    }
    public function getObjectAsync(array $args)
    {
        $provider = $this->getMaterialsProvider($args);
        unset($args['@MaterialsProvider']);
        $instructionFileSuffix = $this->getInstructionFileSuffix($args);
        unset($args['@InstructionFileSuffix']);
        $strategy = $this->getMetadataStrategy($args, $instructionFileSuffix);
        unset($args['@MetadataStrategy']);
        $saveAs = null;
        if (!empty($args['SaveAs'])) {
            $saveAs = $args['SaveAs'];
        }
        $promise = $this->client->getObjectAsync($args)
            ->then(
                function ($result) use (
                    $provider,
                    $instructionFileSuffix,
                    $strategy,
                    $args
                ) {
                    if ($strategy === null) {
                        $strategy = $this->determineGetObjectStrategy(
                            $result,
                            $instructionFileSuffix
                        );
                    }
                    $envelope = $strategy->load($args + [
                        'Metadata' => $result['Metadata']
                    ]);
                    $provider = $provider->fromDecryptionEnvelope($envelope);
                    $result['Body'] = $this->decrypt(
                        $result['Body'],
                        $provider,
                        $envelope,
                        isset($args['@CipherOptions'])
                            ? $args['@CipherOptions']
                            : []
                    );
                    return $result;
                }
            )->then(
                function ($result) use ($saveAs) {
                    if (!empty($saveAs)) {
                        file_put_contents(
                            $saveAs,
                            (string)$result['Body'],
                            LOCK_EX
                        );
                    }
                    return $result;
                }
            );
        return $promise;
    }
    public function getObject(array $args)
    {
        return $this->getObjectAsync($args)->wait();
    }
}
