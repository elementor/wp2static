<?php
namespace Aws\S3;
use Aws\Common\Signature\SignatureV4;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;
class S3SignatureV4 extends SignatureV4 implements S3SignatureInterface
{
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request->setHeader(
                'x-amz-content-sha256',
                $this->getPayload($request)
            );
        }
        parent::signRequest($request, $credentials);
    }
    protected function getPresignedPayload(RequestInterface $request)
    {
        return 'UNSIGNED-PAYLOAD';
    }
    protected function createCanonicalizedPath(RequestInterface $request)
    {
        return '/' . ltrim($request->getPath(), '/');
    }
}
