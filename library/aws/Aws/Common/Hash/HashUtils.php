<?php
namespace Aws\Common\Hash;
use Aws\Common\Exception\InvalidArgumentException;
class HashUtils
{
    public static function hexToBin($hash)
    {
        static $useNative;
        if ($useNative === null) {
            $useNative = function_exists('hex2bin');
        }
        if (!$useNative && strlen($hash) % 2 !== 0) {
            $hash = '0' . $hash;
        }
        return $useNative ? hex2bin($hash) : pack("H*", $hash);
    }
    public static function binToHex($hash)
    {
        return bin2hex($hash);
    }
    public static function validateAlgorithm($algorithm)
    {
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new InvalidArgumentException("The hashing algorithm specified ({$algorithm}) does not exist.");
        }
        return true;
    }
}
