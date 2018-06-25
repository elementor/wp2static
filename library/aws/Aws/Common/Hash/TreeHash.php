<?php
namespace Aws\Common\Hash;
use Aws\Common\Enum\Size;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\LogicException;
use Guzzle\Http\EntityBody;
class TreeHash implements ChunkHashInterface
{
    protected $algorithm;
    protected $checksums = array();
    protected $hash;
    protected $hashRaw;
    public static function fromChecksums(array $checksums, $inBinaryForm = false, $algorithm = self::DEFAULT_ALGORITHM)
    {
        $treeHash = new self($algorithm);
        $treeHash->checksums = $inBinaryForm ? $checksums : array_map('Aws\Common\Hash\HashUtils::hexToBin', $checksums);
        $treeHash->getHash();
        return $treeHash;
    }
    public static function fromContent($content, $algorithm = self::DEFAULT_ALGORITHM)
    {
        $treeHash = new self($algorithm);
        $content = EntityBody::factory($content);
        while ($data = $content->read(Size::MB)) {
            $treeHash->addData($data);
        }
        $treeHash->getHash();
        return $treeHash;
    }
    public static function validateChecksum($content, $checksum, $algorithm = self::DEFAULT_ALGORITHM)
    {
        $treeHash = self::fromContent($content, $algorithm);
        return ($checksum === $treeHash->getHash());
    }
    public function __construct($algorithm = self::DEFAULT_ALGORITHM)
    {
        HashUtils::validateAlgorithm($algorithm);
        $this->algorithm = $algorithm;
    }
    public function addData($data)
    {
        if ($this->hash) {
            throw new LogicException('You may not add more data to a finalized tree hash.');
        }
        if (strlen($data) > Size::MB) {
            throw new InvalidArgumentException('The chunk of data added is too large for tree hashing.');
        }
        $this->checksums[] = hash($this->algorithm, $data, true);
        return $this;
    }
    public function addChecksum($checksum, $inBinaryForm = false)
    {
        if ($this->hash) {
            throw new LogicException('You may not add more checksums to a finalized tree hash.');
        }
        $this->checksums[] = $inBinaryForm ? $checksum : HashUtils::hexToBin($checksum);
        return $this;
    }
    public function getHash($returnBinaryForm = false)
    {
        if (!$this->hash) {
            $hashes = $this->checksums;
            while (count($hashes) > 1) {
                $sets = array_chunk($hashes, 2);
                $hashes = array();
                foreach ($sets as $set) {
                    $hashes[] = (count($set) === 1) ? $set[0] : hash($this->algorithm, $set[0] . $set[1], true);
                }
            }
            $this->hashRaw = $hashes[0];
            $this->hash = HashUtils::binToHex($this->hashRaw);
        }
        return $returnBinaryForm ? $this->hashRaw : $this->hash;
    }
    public function getChecksums()
    {
        return $this->checksums;
    }
}
