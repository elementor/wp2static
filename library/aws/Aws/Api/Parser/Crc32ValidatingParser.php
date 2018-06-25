<?php
namespace Aws\Api\Parser;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7;
class Crc32ValidatingParser extends AbstractParser
{
    private $parser;
    public function __construct(callable $parser)
    {
        $this->parser = $parser;
    }
    public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    ) {
        if ($expected = $response->getHeaderLine('x-amz-crc32')) {
            $hash = hexdec(Psr7\hash($response->getBody(), 'crc32b'));
            if ($expected != $hash) {
                throw new AwsException(
                    "crc32 mismatch. Expected {$expected}, found {$hash}.",
                    $command,
                    [
                        'code'             => 'ClientChecksumMismatch',
                        'connection_error' => true,
                        'response'         => $response
                    ]
                );
            }
        }
        $fn = $this->parser;
        return $fn($command, $response);
    }
}
