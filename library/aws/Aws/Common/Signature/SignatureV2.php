<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;
class SignatureV2 extends AbstractSignature
{
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        $timestamp = $this->getTimestamp(true);
        $this->addParameter($request, 'Timestamp', gmdate('c', $timestamp));
        $this->addParameter($request, 'SignatureVersion', '2');
        $this->addParameter($request, 'SignatureMethod', 'HmacSHA256');
        $this->addParameter($request, 'AWSAccessKeyId', $credentials->getAccessKeyId());
        if ($token = $credentials->getSecurityToken()) {
            $this->addParameter($request, 'SecurityToken', $token);
        }
        $path = '/' . ltrim($request->getUrl(true)->normalizePath()->getPath(), '/');
        $sign = $request->getMethod() . "\n"
            . $request->getHost() . "\n"
            . $path . "\n"
            . $this->getCanonicalizedParameterString($request);
        $request->getParams()->set('aws.string_to_sign', $sign);
        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $sign,
                $credentials->getSecretKey(),
                true
            )
        );
        $this->addParameter($request, 'Signature', $signature);
    }
    public function addParameter(RequestInterface $request, $key, $value)
    {
        if ($request->getMethod() == 'POST') {
            $request->setPostField($key, $value);
        } else {
            $request->getQuery()->set($key, $value);
        }
    }
    private function getCanonicalizedParameterString(RequestInterface $request)
    {
        if ($request->getMethod() == 'POST') {
            $params = $request->getPostFields()->toArray();
        } else {
            $params = $request->getQuery()->toArray();
        }
        unset($params['Signature']);
        uksort($params, 'strcmp');
        $str = '';
        foreach ($params as $key => $val) {
            $str .= rawurlencode($key) . '=' . rawurlencode($val) . '&';
        }
        return substr($str, 0, -1);
    }
}
