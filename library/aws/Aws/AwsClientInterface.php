<?php
namespace Aws;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Promise\PromiseInterface;
interface AwsClientInterface
{
    public function __call($name, array $arguments);
    public function getCommand($name, array $args = []);
    public function execute(CommandInterface $command);
    public function executeAsync(CommandInterface $command);
    public function getCredentials();
    public function getRegion();
    public function getEndpoint();
    public function getApi();
    public function getConfig($option = null);
    public function getHandlerList();
    public function getIterator($name, array $args = []);
    public function getPaginator($name, array $args = []);
    public function waitUntil($name, array $args = []);
    public function getWaiter($name, array $args = []);
}
