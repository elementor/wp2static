<?php
namespace Aws\Common\Hash;
interface ChunkHashInterface
{
    const DEFAULT_ALGORITHM = 'sha256';
    public function __construct($algorithm = 'sha256');
    public function addData($data);
    public function getHash($returnBinaryForm = false);
}
