<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Enum\DateFormat;
use Guzzle\Http\Message\RequestInterface;
class SignatureV3Https extends AbstractSignature
{
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        if (!$request->hasHeader('date') && !$request->hasHeader('x-amz-date')) {
            $request->setHeader('Date', gmdate(DateFormat::RFC1123, $this->getTimestamp()));
        }
        if ($credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $credentials->getSecurityToken());
        }
        $stringToSign = (string) ($request->getHeader('Date') ?: $request->getHeader('x-amz-date'));
        $request->getParams()->set('aws.string_to_sign', $stringToSign);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $credentials->getSecretKey(), true));
        $headerFormat = 'AWS3-HTTPS AWSAccessKeyId=%s,Algorithm=HmacSHA256,Signature=%s';
        $request->setHeader('X-Amzn-Authorization', sprintf($headerFormat, $credentials->getAccessKeyId(), $signature));
    }
}
