<?php
namespace Aws\Common\Hash;
use Aws\Common\Exception\LogicException;
class ChunkHash implements ChunkHashInterface
{
    protected $context;
    protected $hash;
    protected $hashRaw;
    public function __construct($algorithm = self::DEFAULT_ALGORITHM)
    {
        HashUtils::validateAlgorithm($algorithm);
        $this->context = hash_init($algorithm);
    }
    public function addData($data)
    {
        if (!$this->context) {
            throw new LogicException('You may not add more data to a finalized chunk hash.');
        }
        hash_update($this->context, $data);
        return $this;
    }
    public function getHash($returnBinaryForm = false)
    {
        if (!$this->hash) {
            $this->hashRaw = hash_final($this->context, true);
            $this->hash = HashUtils::binToHex($this->hashRaw);
            $this->context = null;
        }
        return $returnBinaryForm ? $this->hashRaw : $this->hash;
    }
    public function __clone()
    {
        if ($this->context) {
            $this->context = hash_copy($this->context);
        }
    }
}
