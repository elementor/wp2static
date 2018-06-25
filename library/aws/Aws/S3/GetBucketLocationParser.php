<?php
namespace Aws\S3;
use Aws\Api\Parser\AbstractParser;
use Aws\CommandInterface;
use Psr\Http\Message\ResponseInterface;
class GetBucketLocationParser extends AbstractParser
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
        $fn = $this->parser;
        $result = $fn($command, $response);
        if ($command->getName() === 'GetBucketLocation') {
            $location = 'us-east-1';
            if (preg_match('/>(.+?)<\/LocationConstraint>/', $response->getBody(), $matches)) {
                $location = $matches[1] === 'EU' ? 'eu-west-1' : $matches[1];
            }
            $result['LocationConstraint'] = $location;
        }
        return $result;
    }
}
