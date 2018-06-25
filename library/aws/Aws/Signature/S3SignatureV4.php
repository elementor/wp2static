<?php
namespace Aws\Signature;
use Aws\Credentials\CredentialsInterface;
use Psr\Http\Message\RequestInterface;
class S3SignatureV4 extends SignatureV4
{
    public function signRequest(
        RequestInterface $request,
        CredentialsInterface $credentials
    ) {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader(
                'X-Amz-Content-Sha256',
                $this->getPayload($request)
            );
        }
        return parent::signRequest($request, $credentials);
    }
    public function presign(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires,
        array $options = []
    ) {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader(
                'X-Amz-Content-Sha256',
                $this->getPresignedPayload($request)
            );
        }
        return parent::presign($request, $credentials, $expires, $options);
    }
    protected function getPresignedPayload(RequestInterface $request)
    {
        return SignatureV4::UNSIGNED_PAYLOAD;
    }
    protected function createCanonicalizedPath($path)
    {
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        return '/' . $path;
    }
}
