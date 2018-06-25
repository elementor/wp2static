<?php
namespace Aws\S3;
use Aws\CacheInterface;
use Aws\LruArrayCache;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;
class StreamWrapper
{
    public $context;
    private $body;
    private $size;
    private $params = [];
    private $mode;
    private $objectIterator;
    private $openedBucket;
    private $openedBucketPrefix;
    private $openedPath;
    private $cache;
    private $protocol = 's3';
    public static function register(
        S3ClientInterface $client,
        $protocol = 's3',
        CacheInterface $cache = null
    ) {
        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }
        stream_wrapper_register($protocol, get_called_class(), STREAM_IS_URL);
        $default = stream_context_get_options(stream_context_get_default());
        $default[$protocol]['client'] = $client;
        if ($cache) {
            $default[$protocol]['cache'] = $cache;
        } elseif (!isset($default[$protocol]['cache'])) {
            $default[$protocol]['cache'] = new LruArrayCache();
        }
        stream_context_set_default($default);
    }
    public function stream_close()
    {
        $this->body = $this->cache = null;
    }
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->initProtocol($path);
        $this->params = $this->getBucketKey($path);
        $this->mode = rtrim($mode, 'bt');
        if ($errors = $this->validate($path, $this->mode)) {
            return $this->triggerError($errors);
        }
        return $this->boolCall(function() use ($path) {
            switch ($this->mode) {
                case 'r': return $this->openReadStream($path);
                case 'a': return $this->openAppendStream($path);
                default: return $this->openWriteStream($path);
            }
        });
    }
    public function stream_eof()
    {
        return $this->body->eof();
    }
    public function stream_flush()
    {
        if ($this->mode == 'r') {
            return false;
        }
        if ($this->body->isSeekable()) {
            $this->body->seek(0);
        }
        $params = $this->getOptions(true);
        $params['Body'] = $this->body;
        if (!isset($params['ContentType']) &&
            ($type = Psr7\mimetype_from_filename($params['Key']))
        ) {
            $params['ContentType'] = $type;
        }
        $this->clearCacheKey("s3:
        return $this->boolCall(function () use ($params) {
            return (bool) $this->getClient()->putObject($params);
        });
    }
    public function stream_read($count)
    {
        return $this->body->read($count);
    }
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return !$this->body->isSeekable()
            ? false
            : $this->boolCall(function () use ($offset, $whence) {
                $this->body->seek($offset, $whence);
                return true;
            });
    }
    public function stream_tell()
    {
        return $this->boolCall(function() { return $this->body->tell(); });
    }
    public function stream_write($data)
    {
        return $this->body->write($data);
    }
    public function unlink($path)
    {
        $this->initProtocol($path);
        return $this->boolCall(function () use ($path) {
            $this->clearCacheKey($path);
            $this->getClient()->deleteObject($this->withPath($path));
            return true;
        });
    }
    public function stream_stat()
    {
        $stat = $this->getStatTemplate();
        $stat[7] = $stat['size'] = $this->getSize();
        $stat[2] = $stat['mode'] = $this->mode;
        return $stat;
    }
    public function url_stat($path, $flags)
    {
        $this->initProtocol($path);
        $split = explode(':
        $path = strtolower($split[0]) . ':
        if ($value = $this->getCacheStorage()->get($path)) {
            return $value;
        }
        $stat = $this->createStat($path, $flags);
        if (is_array($stat)) {
            $this->getCacheStorage()->set($path, $stat);
        }
        return $stat;
    }
    private function initProtocol($path)
    {
        $parts = explode(':
        $this->protocol = $parts[0] ?: 's3';
    }
    private function createStat($path, $flags)
    {
        $this->initProtocol($path);
        $parts = $this->withPath($path);
        if (!$parts['Key']) {
            return $this->statDirectory($parts, $path, $flags);
        }
        return $this->boolCall(function () use ($parts, $path) {
            try {
                $result = $this->getClient()->headObject($parts);
                if (substr($parts['Key'], -1, 1) == '/' &&
                    $result['ContentLength'] == 0
                ) {
                    return $this->formatUrlStat($path);
                }
                return $this->formatUrlStat($result->toArray());
            } catch (S3Exception $e) {
                $result = $this->getClient()->listObjects([
                    'Bucket'  => $parts['Bucket'],
                    'Prefix'  => rtrim($parts['Key'], '/') . '/',
                    'MaxKeys' => 1
                ]);
                if (!$result['Contents'] && !$result['CommonPrefixes']) {
                    throw new \Exception("File or directory not found: $path");
                }
                return $this->formatUrlStat($path);
            }
        }, $flags);
    }
    private function statDirectory($parts, $path, $flags)
    {
        if (!$parts['Bucket'] ||
            $this->getClient()->doesBucketExist($parts['Bucket'])
        ) {
            return $this->formatUrlStat($path);
        }
        return $this->triggerError("File or directory not found: $path", $flags);
    }
    public function mkdir($path, $mode, $options)
    {
        $this->initProtocol($path);
        $params = $this->withPath($path);
        $this->clearCacheKey($path);
        if (!$params['Bucket']) {
            return false;
        }
        if (!isset($params['ACL'])) {
            $params['ACL'] = $this->determineAcl($mode);
        }
        return empty($params['Key'])
            ? $this->createBucket($path, $params)
            : $this->createSubfolder($path, $params);
    }
    public function rmdir($path, $options)
    {
        $this->initProtocol($path);
        $this->clearCacheKey($path);
        $params = $this->withPath($path);
        $client = $this->getClient();
        if (!$params['Bucket']) {
            return $this->triggerError('You must specify a bucket');
        }
        return $this->boolCall(function () use ($params, $path, $client) {
            if (!$params['Key']) {
                $client->deleteBucket(['Bucket' => $params['Bucket']]);
                return true;
            }
            return $this->deleteSubfolder($path, $params);
        });
    }
    public function dir_opendir($path, $options)
    {
        $this->initProtocol($path);
        $this->openedPath = $path;
        $params = $this->withPath($path);
        $delimiter = $this->getOption('delimiter');
        $filterFn = $this->getOption('listFilter');
        $op = ['Bucket' => $params['Bucket']];
        $this->openedBucket = $params['Bucket'];
        if ($delimiter === null) {
            $delimiter = '/';
        }
        if ($delimiter) {
            $op['Delimiter'] = $delimiter;
        }
        if ($params['Key']) {
            $params['Key'] = rtrim($params['Key'], $delimiter) . $delimiter;
            $op['Prefix'] = $params['Key'];
        }
        $this->openedBucketPrefix = $params['Key'];
        $this->objectIterator = \Aws\flatmap(
            $this->getClient()->getPaginator('ListObjects', $op),
            function (Result $result) use ($filterFn) {
                $contentsAndPrefixes = $result->search('[Contents[], CommonPrefixes[]][]');
                return array_filter(
                    $contentsAndPrefixes,
                    function ($key) use ($filterFn) {
                        return (!$filterFn || call_user_func($filterFn, $key))
                            && (!isset($key['Key']) || substr($key['Key'], -1, 1) !== '/');
                    }
                );
            }
        );
        return true;
    }
    public function dir_closedir()
    {
        $this->objectIterator = null;
        gc_collect_cycles();
        return true;
    }
    public function dir_rewinddir()
    {
        $this->boolCall(function() {
            $this->objectIterator = null;
            $this->dir_opendir($this->openedPath, null);
            return true;
        });
    }
    public function dir_readdir()
    {
        if (!$this->objectIterator->valid()) {
            return false;
        }
        $cur = $this->objectIterator->current();
        if (isset($cur['Prefix'])) {
            $result = rtrim($cur['Prefix'], '/');
            $key = $this->formatKey($result);
            $stat = $this->formatUrlStat($key);
        } else {
            $result = $cur['Key'];
            $key = $this->formatKey($cur['Key']);
            $stat = $this->formatUrlStat($cur);
        }
        $this->getCacheStorage()->set($key, $stat);
        $this->objectIterator->next();
        return $this->openedBucketPrefix
            ? substr($result, strlen($this->openedBucketPrefix))
            : $result;
    }
    private function formatKey($key)
    {
        $protocol = explode(':
        return "{$protocol}:
    }
    public function rename($path_from, $path_to)
    {
        $this->initProtocol($path_from);
        $partsFrom = $this->withPath($path_from);
        $partsTo = $this->withPath($path_to);
        $this->clearCacheKey($path_from);
        $this->clearCacheKey($path_to);
        if (!$partsFrom['Key'] || !$partsTo['Key']) {
            return $this->triggerError('The Amazon S3 stream wrapper only '
                . 'supports copying objects');
        }
        return $this->boolCall(function () use ($partsFrom, $partsTo) {
            $options = $this->getOptions(true);
            $this->getClient()->copy(
                $partsFrom['Bucket'],
                $partsFrom['Key'],
                $partsTo['Bucket'],
                $partsTo['Key'],
                isset($options['acl']) ? $options['acl'] : 'private',
                $options
            );
            $this->getClient()->deleteObject([
                'Bucket' => $partsFrom['Bucket'],
                'Key'    => $partsFrom['Key']
            ] + $options);
            return true;
        });
    }
    public function stream_cast($cast_as)
    {
        return false;
    }
    private function validate($path, $mode)
    {
        $errors = [];
        if (!$this->getOption('Key')) {
            $errors[] = 'Cannot open a bucket. You must specify a path in the '
                . 'form of s3:
        }
        if (!in_array($mode, ['r', 'w', 'a', 'x'])) {
            $errors[] = "Mode not supported: {$mode}. "
                . "Use one 'r', 'w', 'a', or 'x'.";
        }
        if ($mode == 'x' &&
            $this->getClient()->doesObjectExist(
                $this->getOption('Bucket'),
                $this->getOption('Key'),
                $this->getOptions(true)
            )
        ) {
            $errors[] = "{$path} already exists on Amazon S3";
        }
        return $errors;
    }
    private function getOptions($removeContextData = false)
    {
        if ($this->context === null) {
            $options = [];
        } else {
            $options = stream_context_get_options($this->context);
            $options = isset($options[$this->protocol])
                ? $options[$this->protocol]
                : [];
        }
        $default = stream_context_get_options(stream_context_get_default());
        $default = isset($default[$this->protocol])
            ? $default[$this->protocol]
            : [];
        $result = $this->params + $options + $default;
        if ($removeContextData) {
            unset($result['client'], $result['seekable'], $result['cache']);
        }
        return $result;
    }
    private function getOption($name)
    {
        $options = $this->getOptions();
        return isset($options[$name]) ? $options[$name] : null;
    }
    private function getClient()
    {
        if (!$client = $this->getOption('client')) {
            throw new \RuntimeException('No client in stream context');
        }
        return $client;
    }
    private function getBucketKey($path)
    {
        $parts = explode(':
        $parts = explode('/', $parts[1], 2);
        return [
            'Bucket' => $parts[0],
            'Key'    => isset($parts[1]) ? $parts[1] : null
        ];
    }
    private function withPath($path)
    {
        $params = $this->getOptions(true);
        return $this->getBucketKey($path) + $params;
    }
    private function openReadStream()
    {
        $client = $this->getClient();
        $command = $client->getCommand('GetObject', $this->getOptions(true));
        $command['@http']['stream'] = true;
        $result = $client->execute($command);
        $this->size = $result['ContentLength'];
        $this->body = $result['Body'];
        if ($this->getOption('seekable') && !$this->body->isSeekable()) {
            $this->body = new CachingStream($this->body);
        }
        return true;
    }
    private function openWriteStream()
    {
        $this->body = new Stream(fopen('php:
        return true;
    }
    private function openAppendStream()
    {
        try {
            $client = $this->getClient();
            $this->body = $client->getObject($this->getOptions(true))['Body'];
            $this->body->seek(0, SEEK_END);
            return true;
        } catch (S3Exception $e) {
            return $this->openWriteStream();
        }
    }
    private function triggerError($errors, $flags = null)
    {
        if ($flags & STREAM_URL_STAT_QUIET) {
            return $flags & STREAM_URL_STAT_LINK
                ? $this->formatUrlStat(false)
                : false;
        }
        trigger_error(implode("\n", (array) $errors), E_USER_WARNING);
        return false;
    }
    private function formatUrlStat($result = null)
    {
        $stat = $this->getStatTemplate();
        switch (gettype($result)) {
            case 'NULL':
            case 'string':
                $stat['mode'] = $stat[2] = 0040777;
                break;
            case 'array':
                $stat['mode'] = $stat[2] = 0100777;
                if (isset($result['ContentLength'])) {
                    $stat['size'] = $stat[7] = $result['ContentLength'];
                } elseif (isset($result['Size'])) {
                    $stat['size'] = $stat[7] = $result['Size'];
                }
                if (isset($result['LastModified'])) {
                    $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10]
                        = strtotime($result['LastModified']);
                }
        }
        return $stat;
    }
    private function createBucket($path, array $params)
    {
        if ($this->getClient()->doesBucketExist($params['Bucket'])) {
            return $this->triggerError("Bucket already exists: {$path}");
        }
        return $this->boolCall(function () use ($params, $path) {
            $this->getClient()->createBucket($params);
            $this->clearCacheKey($path);
            return true;
        });
    }
    private function createSubfolder($path, array $params)
    {
        $params['Key'] = rtrim($params['Key'], '/') . '/';
        $params['Body'] = '';
        if ($this->getClient()->doesObjectExist(
            $params['Bucket'],
            $params['Key'])
        ) {
            return $this->triggerError("Subfolder already exists: {$path}");
        }
        return $this->boolCall(function () use ($params, $path) {
            $this->getClient()->putObject($params);
            $this->clearCacheKey($path);
            return true;
        });
    }
    private function deleteSubfolder($path, $params)
    {
        $prefix = rtrim($params['Key'], '/') . '/';
        $result = $this->getClient()->listObjects([
            'Bucket'  => $params['Bucket'],
            'Prefix'  => $prefix,
            'MaxKeys' => 1
        ]);
        if ($contents = $result['Contents']) {
            return (count($contents) > 1 || $contents[0]['Key'] != $prefix)
                ? $this->triggerError('Subfolder is not empty')
                : $this->unlink(rtrim($path, '/') . '/');
        }
        return $result['CommonPrefixes']
            ? $this->triggerError('Subfolder contains nested folders')
            : true;
    }
    private function determineAcl($mode)
    {
        switch (substr(decoct($mode), 0, 1)) {
            case '7': return 'public-read';
            case '6': return 'authenticated-read';
            default: return 'private';
        }
    }
    private function getStatTemplate()
    {
        return [
            0  => 0,  'dev'     => 0,
            1  => 0,  'ino'     => 0,
            2  => 0,  'mode'    => 0,
            3  => 0,  'nlink'   => 0,
            4  => 0,  'uid'     => 0,
            5  => 0,  'gid'     => 0,
            6  => -1, 'rdev'    => -1,
            7  => 0,  'size'    => 0,
            8  => 0,  'atime'   => 0,
            9  => 0,  'mtime'   => 0,
            10 => 0,  'ctime'   => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks'  => -1,
        ];
    }
    private function boolCall(callable $fn, $flags = null)
    {
        try {
            return $fn();
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage(), $flags);
        }
    }
    private function getCacheStorage()
    {
        if (!$this->cache) {
            $this->cache = $this->getOption('cache') ?: new LruArrayCache();
        }
        return $this->cache;
    }
    private function clearCacheKey($key)
    {
        clearstatcache(true, $key);
        $this->getCacheStorage()->remove($key);
    }
    private function getSize()
    {
        $size = $this->body->getSize();
        return $size !== null ? $size : $this->size;
    }
}
