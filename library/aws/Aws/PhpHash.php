<?php
namespace Aws;
class PhpHash implements HashInterface
{
    private $context;
    private $algo;
    private $options;
    private $hash;
    public function __construct($algo, array $options = [])
    {
        $this->algo = $algo;
        $this->options = $options;
    }
    public function update($data)
    {
        if ($this->hash !== null) {
            $this->reset();
        }
        hash_update($this->getContext(), $data);
    }
    public function complete()
    {
        if ($this->hash) {
            return $this->hash;
        }
        $this->hash = hash_final($this->getContext(), true);
        if (isset($this->options['base64']) && $this->options['base64']) {
            $this->hash = base64_encode($this->hash);
        }
        return $this->hash;
    }
    public function reset()
    {
        $this->context = $this->hash = null;
    }
    private function getContext()
    {
        if (!$this->context) {
            $key = isset($this->options['key']) ? $this->options['key'] : null;
            $this->context = hash_init(
                $this->algo,
                $key ? HASH_HMAC : 0,
                $key
            );
        }
        return $this->context;
    }
}
