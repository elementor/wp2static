<?php
namespace Aws\Common\Signature;
use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Enum\DateFormat;
use Aws\Common\HostNameUtils;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use Guzzle\Stream\Stream;
class SignatureV4 extends AbstractSignature implements EndpointSignatureInterface
{
    const DEFAULT_PAYLOAD = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
    protected $serviceName;
    protected $regionName;
    protected $maxCacheSize = 50;
    protected $hashCache = array();
    protected $cacheSize = 0;
    public function __construct($serviceName = null, $regionName = null)
    {
        $this->serviceName = $serviceName;
        $this->regionName = $regionName;
    }
    public function setServiceName($service)
    {
        $this->serviceName = $service;
        return $this;
    }
    public function setRegionName($region)
    {
        $this->regionName = $region;
        return $this;
    }
    public function setMaxCacheSize($maxCacheSize)
    {
        $this->maxCacheSize = $maxCacheSize;
        return $this;
    }
    public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
    {
        $timestamp = $this->getTimestamp();
        $longDate = gmdate(DateFormat::ISO8601, $timestamp);
        $shortDate = substr($longDate, 0, 8);
        $request->removeHeader('Authorization');
        if ($request->hasHeader('x-amz-date') || !$request->hasHeader('Date')) {
            $request->setHeader('x-amz-date', $longDate);
        } else {
            $request->setHeader('Date', gmdate(DateFormat::RFC1123, $timestamp));
        }
        if ($credentials->getSecurityToken()) {
            $request->setHeader('x-amz-security-token', $credentials->getSecurityToken());
        }
        $region = $this->regionName;
        $service = $this->serviceName;
        if (!$region || !$service) {
            $url = Url::factory($request->getUrl());
            $region = $region ?: HostNameUtils::parseRegionName($url);
            $service = $service ?: HostNameUtils::parseServiceName($url);
        }
        $credentialScope = $this->createScope($shortDate, $region, $service);
        $payload = $this->getPayload($request);
        $signingContext = $this->createSigningContext($request, $payload);
        $signingContext['string_to_sign'] = $this->createStringToSign(
            $longDate,
            $credentialScope,
            $signingContext['canonical_request']
        );
        $signingKey = $this->getSigningKey($shortDate, $region, $service, $credentials->getSecretKey());
        $signature = hash_hmac('sha256', $signingContext['string_to_sign'], $signingKey);
        $request->setHeader('Authorization', "AWS4-HMAC-SHA256 "
            . "Credential={$credentials->getAccessKeyId()}/{$credentialScope}, "
            . "SignedHeaders={$signingContext['signed_headers']}, Signature={$signature}");
        $request->getParams()->set('aws.signature', $signingContext);
    }
    public function createPresignedUrl(
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        $request = $this->createPresignedRequest($request, $credentials);
        $query = $request->getQuery();
        $httpDate = gmdate(DateFormat::ISO8601, $this->getTimestamp());
        $shortDate = substr($httpDate, 0, 8);
        $scope = $this->createScope(
            $shortDate,
            $this->regionName,
            $this->serviceName
        );
        $this->addQueryValues($scope, $request, $credentials, $expires);
        $payload = $this->getPresignedPayload($request);
        $context = $this->createSigningContext($request, $payload);
        $stringToSign = $this->createStringToSign(
            $httpDate,
            $scope,
            $context['canonical_request']
        );
        $key = $this->getSigningKey(
            $shortDate,
            $this->regionName,
            $this->serviceName,
            $credentials->getSecretKey()
        );
        $query['X-Amz-Signature'] = hash_hmac('sha256', $stringToSign, $key);
        return $request->getUrl();
    }
    public static function convertPostToGet(EntityEnclosingRequestInterface $request)
    {
        if ($request->getMethod() !== 'POST') {
            throw new \InvalidArgumentException('Expected a POST request but '
                . 'received a ' . $request->getMethod() . ' request.');
        }
        $cloned = RequestFactory::getInstance()
            ->cloneRequestWithMethod($request, 'GET');
        foreach ($request->getPostFields() as $name => $value) {
            $cloned->getQuery()->set($name, $value);
        }
        return $cloned;
    }
    protected function getPayload(RequestInterface $request)
    {
        if ($request->hasHeader('x-amz-content-sha256')) {
            return (string) $request->getHeader('x-amz-content-sha256');
        }
        if ($request instanceof EntityEnclosingRequestInterface) {
            if ($request->getMethod() == 'POST' && count($request->getPostFields())) {
                return hash('sha256', (string) $request->getPostFields());
            } elseif ($body = $request->getBody()) {
                return Stream::getHash($request->getBody(), 'sha256');
            }
        }
        return self::DEFAULT_PAYLOAD;
    }
    protected function getPresignedPayload(RequestInterface $request)
    {
        return $this->getPayload($request);
    }
    protected function createCanonicalizedPath(RequestInterface $request)
    {
        $doubleEncoded = rawurlencode(ltrim($request->getPath(), '/'));
        return '/' . str_replace('%2F', '/', $doubleEncoded);
    }
    private function createStringToSign($longDate, $credentialScope, $creq)
    {
        return "AWS4-HMAC-SHA256\n{$longDate}\n{$credentialScope}\n"
            . hash('sha256', $creq);
    }
    private function createPresignedRequest(
        RequestInterface $request,
        CredentialsInterface $credentials
    ) {
        if ($request instanceof EntityEnclosingRequestInterface
            && $request->getMethod() === 'POST'
            && strpos($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded') === 0
        ) {
            $sr = RequestFactory::getInstance()
                ->cloneRequestWithMethod($request, 'GET');
            foreach ($request->getPostFields() as $name => $value) {
                $sr->getQuery()->set($name, $value);
            }
        } else {
            $sr = clone $request;
        }
        if ($token = $credentials->getSecurityToken()) {
            $sr->setHeader('X-Amz-Security-Token', $token);
            $sr->getQuery()->set('X-Amz-Security-Token', $token);
        }
        $this->moveHeadersToQuery($sr);
        return $sr;
    }
    private function createSigningContext(RequestInterface $request, $payload)
    {
        $signable = array(
            'host'        => true,
            'date'        => true,
            'content-md5' => true
        );
        $canon = $request->getMethod() . "\n"
            . $this->createCanonicalizedPath($request) . "\n"
            . $this->getCanonicalizedQueryString($request) . "\n";
        $canonHeaders = array();
        foreach ($request->getHeaders()->getAll() as $key => $values) {
            $key = strtolower($key);
            if (isset($signable[$key]) || substr($key, 0, 6) === 'x-amz-') {
                $values = $values->toArray();
                if (count($values) == 1) {
                    $values = $values[0];
                } else {
                    sort($values);
                    $values = implode(',', $values);
                }
                $canonHeaders[$key] = $key . ':' . preg_replace('/\s+/', ' ', $values);
            }
        }
        ksort($canonHeaders);
        $signedHeadersString = implode(';', array_keys($canonHeaders));
        $canon .= implode("\n", $canonHeaders) . "\n\n"
            . $signedHeadersString . "\n"
            . $payload;
        return array(
            'canonical_request' => $canon,
            'signed_headers'    => $signedHeadersString
        );
    }
    private function getSigningKey($shortDate, $region, $service, $secretKey)
    {
        $cacheKey = $shortDate . '_' . $region . '_' . $service . '_' . $secretKey;
        if (!isset($this->hashCache[$cacheKey])) {
            if (++$this->cacheSize > $this->maxCacheSize) {
                $this->hashCache = array();
                $this->cacheSize = 0;
            }
            $dateKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
            $regionKey = hash_hmac('sha256', $region, $dateKey, true);
            $serviceKey = hash_hmac('sha256', $service, $regionKey, true);
            $this->hashCache[$cacheKey] = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
        }
        return $this->hashCache[$cacheKey];
    }
    private function getCanonicalizedQueryString(RequestInterface $request)
    {
        $queryParams = $request->getQuery()->getAll();
        unset($queryParams['X-Amz-Signature']);
        if (empty($queryParams)) {
            return '';
        }
        $qs = '';
        ksort($queryParams);
        foreach ($queryParams as $key => $values) {
            if (is_array($values)) {
                sort($values);
            } elseif ($values === 0) {
                $values = array('0');
            } elseif (!$values) {
                $values = array('');
            }
            foreach ((array) $values as $value) {
                if ($value === QueryString::BLANK) {
                    $value = '';
                }
                $qs .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
            }
        }
        return substr($qs, 0, -1);
    }
    private function convertExpires($expires)
    {
        if ($expires instanceof \DateTime) {
            $expires = $expires->getTimestamp();
        } elseif (!is_numeric($expires)) {
            $expires = strtotime($expires);
        }
        $duration = $expires - time();
        if ($duration > 604800) {
            throw new \InvalidArgumentException('The expiration date of a '
                . 'signature version 4 presigned URL must be less than one '
                . 'week');
        }
        return $duration;
    }
    private function createScope($shortDate, $region, $service)
    {
        return $shortDate
            . '/' . $region
            . '/' . $service
            . '/aws4_request';
    }
    private function addQueryValues(
        $scope,
        RequestInterface $request,
        CredentialsInterface $credentials,
        $expires
    ) {
        $credential = $credentials->getAccessKeyId() . '/' . $scope;
        $request->getQuery()
            ->set('X-Amz-Algorithm', 'AWS4-HMAC-SHA256')
            ->set('X-Amz-Credential', $credential)
            ->set('X-Amz-Date', gmdate('Ymd\THis\Z', $this->getTimestamp()))
            ->set('X-Amz-SignedHeaders', 'Host')
            ->set('X-Amz-Expires', $this->convertExpires($expires));
    }
    private function moveHeadersToQuery(RequestInterface $request)
    {
        $query = $request->getQuery();
        foreach ($request->getHeaders() as $name => $header) {
            if (substr($name, 0, 5) == 'x-amz') {
                $query[$header->getName()] = (string) $header;
            }
            if ($name !== 'host') {
                $request->removeHeader($name);
            }
        }
    }
}
