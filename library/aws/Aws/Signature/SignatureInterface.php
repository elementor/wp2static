<?php
namespace Aws\Signature;
use Aws\Credentials\CredentialsInterface;
use Psr\Http\Message\RequestInterface;
interface SignatureInterface
{
    public function signRequest(
        RequestInterface $request,
        CredentialsInterface $credentials
    );
    public function presign(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    );
}
