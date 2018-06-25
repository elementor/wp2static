<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;
interface SignatureInterface
{
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials);
    public function createPresignedUrl(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    );
}
