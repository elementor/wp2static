<?php
namespace Aws;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
class HashingStream implements StreamInterface
{
    use StreamDecoratorTrait;
    private $hash;
    private $callback;
    public function __construct(
        StreamInterface $stream,
        HashInterface $hash,
        callable $onComplete = null
    ) {
        $this->stream = $stream;
        $this->hash = $hash;
        $this->callback = $onComplete;
    }
    public function read($length)
    {
        $data = $this->stream->read($length);
        $this->hash->update($data);
        if ($this->eof()) {
            $result = $this->hash->complete();
            if ($this->callback) {
                call_user_func($this->callback, $result);
            }
        }
        return $data;
    }
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($offset === 0) {
            $this->hash->reset();
            return $this->stream->seek($offset);
        }
        return false;
    }
}
