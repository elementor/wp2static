<?php
namespace Aws;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
function constantly($value)
{
    return function () use ($value) { return $value; };
}
function filter($iterable, callable $pred)
{
    foreach ($iterable as $value) {
        if ($pred($value)) {
            yield $value;
        }
    }
}
function map($iterable, callable $f)
{
    foreach ($iterable as $value) {
        yield $f($value);
    }
}
function flatmap($iterable, callable $f)
{
    foreach (map($iterable, $f) as $outer) {
        foreach ($outer as $inner) {
            yield $inner;
        }
    }
}
function partition($iterable, $size)
{
    $buffer = [];
    foreach ($iterable as $value) {
        $buffer[] = $value;
        if (count($buffer) === $size) {
            yield $buffer;
            $buffer = [];
        }
    }
    if ($buffer) {
        yield $buffer;
    }
}
function or_chain()
{
    $fns = func_get_args();
    return function () use ($fns) {
        $args = func_get_args();
        foreach ($fns as $fn) {
            $result = $args ? call_user_func_array($fn, $args) : $fn();
            if ($result) {
                return $result;
            }
        }
        return null;
    };
}
function load_compiled_json($path)
{
    if ($compiled = @include("$path.php")) {
        return $compiled;
    }
    if (!file_exists($path)) {
        throw new \InvalidArgumentException(
            sprintf("File not found: %s", $path)
        );
    }
    return json_decode(file_get_contents($path), true);
}
function clear_compiled_json()
{
}
function dir_iterator($path, $context = null)
{
    $dh = $context ? opendir($path, $context) : opendir($path);
    if (!$dh) {
        throw new \InvalidArgumentException('File not found: ' . $path);
    }
    while (($file = readdir($dh)) !== false) {
        yield $file;
    }
    closedir($dh);
}
function recursive_dir_iterator($path, $context = null)
{
    $invalid = ['.' => true, '..' => true];
    $pathLen = strlen($path) + 1;
    $iterator = dir_iterator($path, $context);
    $queue = [];
    do {
        while ($iterator->valid()) {
            $file = $iterator->current();
            $iterator->next();
            if (isset($invalid[basename($file)])) {
                continue;
            }
            $fullPath = "{$path}/{$file}";
            yield $fullPath;
            if (is_dir($fullPath)) {
                $queue[] = $iterator;
                $iterator = map(
                    dir_iterator($fullPath, $context),
                    function ($file) use ($fullPath, $pathLen) {
                        return substr("{$fullPath}/{$file}", $pathLen);
                    }
                );
                continue;
            }
        }
        $iterator = array_pop($queue);
    } while ($iterator);
}
function describe_type($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}
function default_http_handler()
{
    $version = (string) ClientInterface::VERSION;
    if ($version[0] === '5') {
        return new \Aws\Handler\GuzzleV5\GuzzleHandler();
    }
    if ($version[0] === '6') {
        return new \Aws\Handler\GuzzleV6\GuzzleHandler();
    }
    throw new \RuntimeException('Unknown Guzzle version: ' . $version);
}
function serialize(CommandInterface $command)
{
    $request = null;
    $handlerList = $command->getHandlerList();
    $handlerList->setHandler(
        function (CommandInterface $_, RequestInterface $r) use (&$request) {
            $request = $r;
            return new FulfilledPromise(new Result([]));
        }
    );
    call_user_func($handlerList->resolve(), $command)->wait();
    if (!$request instanceof RequestInterface) {
        throw new \RuntimeException(
            'Calling handler did not serialize request'
        );
    }
    return $request;
}
function manifest($service = null)
{
    static $manifest = [];
    static $aliases = [];
    if (empty($manifest)) {
        $manifest = load_compiled_json(__DIR__ . '/data/manifest.json');
        foreach ($manifest as $endpoint => $info) {
            $alias = strtolower($info['namespace']);
            if ($alias !== $endpoint) {
                $aliases[$alias] = $endpoint;
            }
        }
    }
    if ($service === null) {
        return $manifest;
    }
    $service = strtolower($service);
    if (isset($manifest[$service])) {
        return $manifest[$service] + ['endpoint' => $service];
    }
    if (isset($aliases[$service])) {
        return manifest($aliases[$service]);
    }
    throw new \InvalidArgumentException(
        "The service \"{$service}\" is not provided by the AWS SDK for PHP."
    );
}
