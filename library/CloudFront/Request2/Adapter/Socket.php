<?php
/**
 * HTTP_Request2_Adapter_Socket
 *
 * @package WP2Static
 */

require_once __DIR__. '/../Adapter.php';
require_once __DIR__. '/../SocketWrapper.php';
class HTTP_Request2_Adapter_Socket extends HTTP_Request2_Adapter
{
    const REGEXP_TOKEN = '[^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+';
    const REGEXP_QUOTED_STRING = '"(?>[^"\\\\]+|\\\\.)*"';
    protected static $sockets = array();
    protected static $challenges = array();
    protected $socket;
    protected $serverChallenge;
    protected $proxyChallenge;
    protected $chunkLength = 0;
    protected $redirectCountdown = null;
    protected $expect100Continue = false;
    public function sendRequest(HTTP_Request2 $request)
    {
        $this->request = $request;

        try {
            $keepAlive = $this->connect();
            $headers   = $this->prepareHeaders();
            $this->socket->write($headers);
            $this->request->setLastEvent('sentHeaders', $headers);

            if (!$this->expect100Continue) {
                $this->writeBody();
                $response = $this->readResponse();

            } else {
                $response = $this->readResponse();
                if (!$response || 100 == $response->getStatus()) {
                    $this->expect100Continue = false;
                    $this->writeBody();
                    $response = $this->readResponse();
                }
            }


            if ($jar = $request->getCookieJar()) {
                $jar->addCookiesFromResponse($response);
            }

            if (!$this->canKeepAlive($keepAlive, $response)) {
                $this->disconnect();
            }

            if ($this->shouldUseProxyDigestAuth($response)) {
                return $this->sendRequest($request);
            }
            if ($this->shouldUseServerDigestAuth($response)) {
                return $this->sendRequest($request);
            }
            if ($authInfo = $response->getHeader('authentication-info')) {
                $this->updateChallenge($this->serverChallenge, $authInfo);
            }
            if ($proxyInfo = $response->getHeader('proxy-authentication-info')) {
                $this->updateChallenge($this->proxyChallenge, $proxyInfo);
            }

        } catch (Exception $e) {
            $this->disconnect();
        }

        unset($this->request, $this->requestBody);

        if (!empty($e)) {
            $this->redirectCountdown = null;
            throw $e;
        }

        if (!$request->getConfig('follow_redirects') || !$response->isRedirect()) {
            $this->redirectCountdown = null;
            return $response;
        } else {
            return $this->handleRedirect($request, $response);
        }
    }
    protected function connect()
    {
        $secure  = 0 == strcasecmp($this->request->getUrl()->getScheme(), 'https');
        $tunnel  = HTTP_Request2::METHOD_CONNECT == $this->request->getMethod();
        $headers = $this->request->getHeaders();
        $reqHost = $this->request->getUrl()->getHost();
        if (!($reqPort = $this->request->getUrl()->getPort())) {
            $reqPort = $secure? 443: 80;
        }

        $httpProxy = $socksProxy = false;
        if (!($host = $this->request->getConfig('proxy_host'))) {
            $host = $reqHost;
            $port = $reqPort;
        } else {
            if (!($port = $this->request->getConfig('proxy_port'))) {
                throw new HTTP_Request2_LogicException(
                    'Proxy port not provided',
                    HTTP_Request2_Exception::MISSING_VALUE
                );
            }
            if ('http' == ($type = $this->request->getConfig('proxy_type'))) {
                $httpProxy = true;
            } elseif ('socks5' == $type) {
                $socksProxy = true;
            } else {
                throw new HTTP_Request2_NotImplementedException(
                    "Proxy type '{$type}' is not supported"
                );
            }
        }

        if ($tunnel && !$httpProxy) {
            throw new HTTP_Request2_LogicException(
                "Trying to perform CONNECT request without proxy",
                HTTP_Request2_Exception::MISSING_VALUE
            );
        }
        if ($secure && !in_array('ssl', stream_get_transports())) {
            throw new HTTP_Request2_LogicException(
                'Need OpenSSL support for https:// requests',
                HTTP_Request2_Exception::MISCONFIGURATION
            );
        }
        if ($httpProxy && !$secure && !empty($headers['connection'])
            && 'Keep-Alive' == $headers['connection']
        ) {
            $this->request->setHeader('connection');
        }

        $keepAlive = ('1.1' == $this->request->getConfig('protocol_version') &&
                      empty($headers['connection'])) ||
                     (!empty($headers['connection']) &&
                      'Keep-Alive' == $headers['connection']);

        $options = array();
        if ($ip = $this->request->getConfig('local_ip')) {
            $options['socket'] = array(
                'bindto' => (false === strpos($ip, ':') ? $ip : '[' . $ip . ']') . ':0'
            );
        }
        if ($secure || $tunnel) {
            $options['ssl'] = array();
            foreach ($this->request->getConfig() as $name => $value) {
                if ('ssl_' == substr($name, 0, 4) && null !== $value) {
                    if ('ssl_verify_host' == $name) {
                        if (version_compare(phpversion(), '5.6', '<')) {
                            if ($value) {
                                $options['ssl']['CN_match'] = $reqHost;
                            }

                        } else {
                            $options['ssl']['verify_peer_name'] = $value;
                            $options['ssl']['peer_name']        = $reqHost;
                        }

                    } else {
                        $options['ssl'][substr($name, 4)] = $value;
                    }
                }
            }
            ksort($options['ssl']);
        }
        if ($timeout = $this->request->getConfig('timeout')) {
            $deadline = time() + $timeout;
        } else {
            $deadline = null;
        }
        // work, we need a new connection if options change
        $remote    = ((!$secure || $httpProxy || $socksProxy)? 'tcp://': 'tls://')
                     . $host . ':' . $port;
        $socketKey = $remote . (
                        ($secure && $httpProxy || $socksProxy)
                        ? "->{$reqHost}:{$reqPort}" : ''
                     ) . (empty($options)? '': ':' . serialize($options));
        unset($this->socket);
        // Ensure that the socket is still connected, see bug #16149
        if ($keepAlive && !empty(self::$sockets[$socketKey])
            && !self::$sockets[$socketKey]->eof()
        ) {
            $this->socket =& self::$sockets[$socketKey];

        } else {
            if ($socksProxy) {
                require_once '../SOCKS5.php';

                $this->socket = new HTTP_Request2_SOCKS5(
                    $remote, $this->request->getConfig('connect_timeout'),
                    $options, $this->request->getConfig('proxy_user'),
                    $this->request->getConfig('proxy_password')
                );
                $this->socket->setDeadline($deadline, $this->request->getConfig('timeout'));
                $this->socket->connect($reqHost, $reqPort);
                if (!$secure) {
                    $conninfo = "tcp://{$reqHost}:{$reqPort} via {$remote}";
                } else {
                    $this->socket->enableCrypto();
                    $conninfo = "tls://{$reqHost}:{$reqPort} via {$remote}";
                }

            } elseif ($secure && $httpProxy && !$tunnel) {
                $this->establishTunnel();
                $conninfo = "tls://{$reqHost}:{$reqPort} via {$remote}";

            } else {
                $this->socket = new HTTP_Request2_SocketWrapper(
                    $remote, $this->request->getConfig('connect_timeout'), $options
                );
            }
            $this->request->setLastEvent('connect', empty($conninfo)? $remote: $conninfo);
            self::$sockets[$socketKey] =& $this->socket;
        }
        $this->socket->setDeadline($deadline, $this->request->getConfig('timeout'));
        return $keepAlive;
    }
    protected function establishTunnel()
    {
        $donor   = new self;
        $connect = new HTTP_Request2(
            $this->request->getUrl(), HTTP_Request2::METHOD_CONNECT,
            array_merge($this->request->getConfig(), array('adapter' => $donor))
        );
        $response = $connect->send();
        if (200 > $response->getStatus() || 300 <= $response->getStatus()) {
            throw new HTTP_Request2_ConnectionException(
                'Failed to connect via HTTPS proxy. Proxy response: ' .
                $response->getStatus() . ' ' . $response->getReasonPhrase()
            );
        }
        $this->socket = $donor->socket;
        $this->socket->enableCrypto();
    }
    protected function canKeepAlive($requestKeepAlive, HTTP_Request2_Response $response)
    {
        if (HTTP_Request2::METHOD_CONNECT == $this->request->getMethod()
            && 200 <= $response->getStatus() && 300 > $response->getStatus()
        ) {
            return true;
        }

        $lengthKnown = 'chunked' == strtolower($response->getHeader('transfer-encoding'))
                       || null !== $response->getHeader('content-length')
                       || HTTP_Request2::METHOD_HEAD == $this->request->getMethod()
                       || in_array($response->getStatus(), array(204, 304));
        $persistent  = 'keep-alive' == strtolower($response->getHeader('connection')) ||
                       (null === $response->getHeader('connection') &&
                        '1.1' == $response->getVersion());
        return $requestKeepAlive && $lengthKnown && $persistent;
    }
    protected function disconnect()
    {
        if (!empty($this->socket)) {
            $this->socket = null;
            $this->request->setLastEvent('disconnect');
        }
    }
    protected function handleRedirect(
        HTTP_Request2 $request, HTTP_Request2_Response $response
    ) {
        if (is_null($this->redirectCountdown)) {
            $this->redirectCountdown = $request->getConfig('max_redirects');
        }
        if (0 == $this->redirectCountdown) {
            $this->redirectCountdown = null;
            throw new HTTP_Request2_MessageException(
                'Maximum (' . $request->getConfig('max_redirects') . ') redirects followed',
                HTTP_Request2_Exception::TOO_MANY_REDIRECTS
            );
        }
        $redirectUrl = new Net_URL2(
            $response->getHeader('location'),
            array(Net_URL2::OPTION_USE_BRACKETS => $request->getConfig('use_brackets'))
        );
        if ($redirectUrl->isAbsolute()
            && !in_array($redirectUrl->getScheme(), array('http', 'https'))
        ) {
            $this->redirectCountdown = null;
            throw new HTTP_Request2_MessageException(
                'Refusing to redirect to a non-HTTP URL ' . $redirectUrl->__toString(),
                HTTP_Request2_Exception::NON_HTTP_REDIRECT
            );
        }
        // but in practice it is often not
        if (!$redirectUrl->isAbsolute()) {
            $redirectUrl = $request->getUrl()->resolve($redirectUrl);
        }
        $redirect = clone $request;
        $redirect->setUrl($redirectUrl);
        if (303 == $response->getStatus()
            || (!$request->getConfig('strict_redirects')
                && in_array($response->getStatus(), array(301, 302)))
        ) {
            $redirect->setMethod(HTTP_Request2::METHOD_GET);
            $redirect->setBody('');
        }

        if (0 < $this->redirectCountdown) {
            $this->redirectCountdown--;
        }
        return $this->sendRequest($redirect);
    }
    protected function shouldUseServerDigestAuth(HTTP_Request2_Response $response)
    {
        if (401 != $response->getStatus() || !$this->request->getAuth()) {
            return false;
        }
        if (!$challenge = $this->parseDigestChallenge($response->getHeader('www-authenticate'))) {
            return false;
        }

        $url    = $this->request->getUrl();
        $scheme = $url->getScheme();
        $host   = $scheme . '://' . $url->getHost();
        if ($port = $url->getPort()) {
            if ((0 == strcasecmp($scheme, 'http') && 80 != $port)
                || (0 == strcasecmp($scheme, 'https') && 443 != $port)
            ) {
                $host .= ':' . $port;
            }
        }

        if (!empty($challenge['domain'])) {
            $prefixes = array();
            foreach (preg_split('/\\s+/', $challenge['domain']) as $prefix) {
                if ('/' == substr($prefix, 0, 1)) {
                    $prefixes[] = $host . $prefix;
                }
            }
        }
        if (empty($prefixes)) {
            $prefixes = array($host . '/');
        }

        $ret = true;
        foreach ($prefixes as $prefix) {
            if (!empty(self::$challenges[$prefix])
                && (empty($challenge['stale']) || strcasecmp('true', $challenge['stale']))
            ) {
                $ret = false;
            }
            self::$challenges[$prefix] =& $challenge;
        }
        return $ret;
    }
    protected function shouldUseProxyDigestAuth(HTTP_Request2_Response $response)
    {
        if (407 != $response->getStatus() || !$this->request->getConfig('proxy_user')) {
            return false;
        }
        if (!($challenge = $this->parseDigestChallenge($response->getHeader('proxy-authenticate')))) {
            return false;
        }

        $key = 'proxy://' . $this->request->getConfig('proxy_host') .
               ':' . $this->request->getConfig('proxy_port');

        if (!empty(self::$challenges[$key])
            && (empty($challenge['stale']) || strcasecmp('true', $challenge['stale']))
        ) {
            $ret = false;
        } else {
            $ret = true;
        }
        self::$challenges[$key] = $challenge;
        return $ret;
    }
    protected function parseDigestChallenge($headerValue)
    {
        $authParam   = '(' . self::REGEXP_TOKEN . ')\\s*=\\s*(' .
                       self::REGEXP_TOKEN . '|' . self::REGEXP_QUOTED_STRING . ')';
        $challenge   = "!(?<=^|\\s|,)Digest ({$authParam}\\s*(,\\s*|$))+!";
        if (!preg_match($challenge, $headerValue, $matches)) {
            return false;
        }

        preg_match_all('!' . $authParam . '!', $matches[0], $params);
        $paramsAry   = array();
        $knownParams = array('realm', 'domain', 'nonce', 'opaque', 'stale',
                             'algorithm', 'qop');
        for ($i = 0; $i < count($params[0]); $i++) {
            if (in_array($params[1][$i], $knownParams)) {
                if ('"' == substr($params[2][$i], 0, 1)) {
                    $paramsAry[$params[1][$i]] = substr($params[2][$i], 1, -1);
                } else {
                    $paramsAry[$params[1][$i]] = $params[2][$i];
                }
            }
        }
        if (!empty($paramsAry['qop'])
            && !in_array('auth', array_map('trim', explode(',', $paramsAry['qop'])))
        ) {
            throw new HTTP_Request2_NotImplementedException(
                "Only 'auth' qop is currently supported in digest authentication, " .
                "server requested '{$paramsAry['qop']}'"
            );
        }
        if (!empty($paramsAry['algorithm']) && 'MD5' != $paramsAry['algorithm']) {
            throw new HTTP_Request2_NotImplementedException(
                "Only 'MD5' algorithm is currently supported in digest authentication, " .
                "server requested '{$paramsAry['algorithm']}'"
            );
        }

        return $paramsAry;
    }
    protected function updateChallenge(&$challenge, $headerValue)
    {
        $authParam   = '!(' . self::REGEXP_TOKEN . ')\\s*=\\s*(' .
                       self::REGEXP_TOKEN . '|' . self::REGEXP_QUOTED_STRING . ')!';
        $paramsAry   = array();

        preg_match_all($authParam, $headerValue, $params);
        for ($i = 0; $i < count($params[0]); $i++) {
            if ('"' == substr($params[2][$i], 0, 1)) {
                $paramsAry[$params[1][$i]] = substr($params[2][$i], 1, -1);
            } else {
                $paramsAry[$params[1][$i]] = $params[2][$i];
            }
        }
        if (!empty($paramsAry['nextnonce'])) {
            $challenge['nonce'] = $paramsAry['nextnonce'];
            $challenge['nc']    = 1;
        }
    }
    protected function createDigestResponse($user, $password, $url, &$challenge)
    {
        if (false !== ($q = strpos($url, '?'))
            && $this->request->getConfig('digest_compat_ie')
        ) {
            $url = substr($url, 0, $q);
        }

        $a1 = md5($user . ':' . $challenge['realm'] . ':' . $password);
        $a2 = md5($this->request->getMethod() . ':' . $url);

        if (empty($challenge['qop'])) {
            $digest = md5($a1 . ':' . $challenge['nonce'] . ':' . $a2);
        } else {
            $challenge['cnonce'] = 'Req2.' . rand();
            if (empty($challenge['nc'])) {
                $challenge['nc'] = 1;
            }
            $nc     = sprintf('%08x', $challenge['nc']++);
            $digest = md5(
                $a1 . ':' . $challenge['nonce'] . ':' . $nc . ':' .
                $challenge['cnonce'] . ':auth:' . $a2
            );
        }
        return 'Digest username="' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $user) . '", ' .
               'realm="' . $challenge['realm'] . '", ' .
               'nonce="' . $challenge['nonce'] . '", ' .
               'uri="' . $url . '", ' .
               'response="' . $digest . '"' .
               (!empty($challenge['opaque'])?
                ', opaque="' . $challenge['opaque'] . '"':
                '') .
               (!empty($challenge['qop'])?
                ', qop="auth", nc=' . $nc . ', cnonce="' . $challenge['cnonce'] . '"':
                '');
    }
    protected function addAuthorizationHeader(&$headers, $requestHost, $requestUrl)
    {
        if (!($auth = $this->request->getAuth())) {
            return;
        }
        switch ($auth['scheme']) {
        case HTTP_Request2::AUTH_BASIC:
            $headers['authorization'] = 'Basic ' . base64_encode(
                $auth['user'] . ':' . $auth['password']
            );
            break;

        case HTTP_Request2::AUTH_DIGEST:
            unset($this->serverChallenge);
            $fullUrl = ('/' == $requestUrl[0])?
                       $this->request->getUrl()->getScheme() . '://' .
                        $requestHost . $requestUrl:
                       $requestUrl;
            foreach (array_keys(self::$challenges) as $key) {
                if ($key == substr($fullUrl, 0, strlen($key))) {
                    $headers['authorization'] = $this->createDigestResponse(
                        $auth['user'], $auth['password'],
                        $requestUrl, self::$challenges[$key]
                    );
                    $this->serverChallenge =& self::$challenges[$key];
                    break;
                }
            }
            break;

        default:
            throw new HTTP_Request2_NotImplementedException(
                "Unknown HTTP authentication scheme '{$auth['scheme']}'"
            );
        }
    }
    protected function addProxyAuthorizationHeader(&$headers, $requestUrl)
    {
        if (!$this->request->getConfig('proxy_host')
            || !($user = $this->request->getConfig('proxy_user'))
            || (0 == strcasecmp('https', $this->request->getUrl()->getScheme())
                && HTTP_Request2::METHOD_CONNECT != $this->request->getMethod())
        ) {
            return;
        }

        $password = $this->request->getConfig('proxy_password');
        switch ($this->request->getConfig('proxy_auth_scheme')) {
        case HTTP_Request2::AUTH_BASIC:
            $headers['proxy-authorization'] = 'Basic ' . base64_encode(
                $user . ':' . $password
            );
            break;

        case HTTP_Request2::AUTH_DIGEST:
            unset($this->proxyChallenge);
            $proxyUrl = 'proxy://' . $this->request->getConfig('proxy_host') .
                        ':' . $this->request->getConfig('proxy_port');
            if (!empty(self::$challenges[$proxyUrl])) {
                $headers['proxy-authorization'] = $this->createDigestResponse(
                    $user, $password,
                    $requestUrl, self::$challenges[$proxyUrl]
                );
                $this->proxyChallenge =& self::$challenges[$proxyUrl];
            }
            break;

        default:
            throw new HTTP_Request2_NotImplementedException(
                "Unknown HTTP authentication scheme '" .
                $this->request->getConfig('proxy_auth_scheme') . "'"
            );
        }
    }
    protected function prepareHeaders()
    {
        $headers = $this->request->getHeaders();
        $url     = $this->request->getUrl();
        $connect = HTTP_Request2::METHOD_CONNECT == $this->request->getMethod();
        $host    = $url->getHost();

        $defaultPort = 0 == strcasecmp($url->getScheme(), 'https')? 443: 80;
        if (($port = $url->getPort()) && $port != $defaultPort || $connect) {
            $host .= ':' . (empty($port)? $defaultPort: $port);
        }
        if (!isset($headers['host'])) {
            $headers['host'] = $host;
        }

        if ($connect) {
            $requestUrl = $host;

        } else {
            if (!$this->request->getConfig('proxy_host')
                || 'http' != $this->request->getConfig('proxy_type')
                || 0 == strcasecmp($url->getScheme(), 'https')
            ) {
                $requestUrl = '';
            } else {
                $requestUrl = $url->getScheme() . '://' . $host;
            }
            $path        = $url->getPath();
            $query       = $url->getQuery();
            $requestUrl .= (empty($path)? '/': $path) . (empty($query)? '': '?' . $query);
        }

        if ('1.1' == $this->request->getConfig('protocol_version')
            && extension_loaded('zlib') && !isset($headers['accept-encoding'])
        ) {
            $headers['accept-encoding'] = 'gzip, deflate';
        }
        if (($jar = $this->request->getCookieJar())
            && ($cookies = $jar->getMatching($this->request->getUrl(), true))
        ) {
            $headers['cookie'] = (empty($headers['cookie'])? '': $headers['cookie'] . '; ') . $cookies;
        }

        $this->addAuthorizationHeader($headers, $host, $requestUrl);
        $this->addProxyAuthorizationHeader($headers, $requestUrl);
        $this->calculateRequestLength($headers);
        if ('1.1' == $this->request->getConfig('protocol_version')) {
            $this->updateExpectHeader($headers);
        } else {
            $this->expect100Continue = false;
        }

        $headersStr = $this->request->getMethod() . ' ' . $requestUrl . ' HTTP/' .
                      $this->request->getConfig('protocol_version') . "\r\n";
        foreach ($headers as $name => $value) {
            $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
            $headersStr   .= $canonicalName . ': ' . $value . "\r\n";
        }
        return $headersStr . "\r\n";
    }
    protected function updateExpectHeader(&$headers)
    {
        $this->expect100Continue = false;
        $expectations = array();
        if (isset($headers['expect'])) {
            if ('' === $headers['expect']) {
                unset($headers['expect']);
                return;
            }
            $expectParam     = ';\s*' . self::REGEXP_TOKEN . '(?:\s*=\s*(?:'
                               . self::REGEXP_TOKEN . '|'
                               . self::REGEXP_QUOTED_STRING . '))?\s*';
            $expectExtension = self::REGEXP_TOKEN . '(?:\s*=\s*(?:'
                               . self::REGEXP_TOKEN . '|'
                               . self::REGEXP_QUOTED_STRING . ')\s*(?:'
                               . $expectParam . ')*)?';
            $expectItem      = '!(100-continue|' . $expectExtension . ')!A';

            $pos    = 0;
            $length = strlen($headers['expect']);

            while ($pos < $length) {
                $pos += strspn($headers['expect'], " \t", $pos);
                if (',' === substr($headers['expect'], $pos, 1)) {
                    $pos++;
                    continue;

                } elseif (!preg_match($expectItem, $headers['expect'], $m, 0, $pos)) {
                    throw new HTTP_Request2_LogicException(
                        "Cannot parse value '{$headers['expect']}' of Expect header",
                        HTTP_Request2_Exception::INVALID_ARGUMENT
                    );

                } else {
                    $pos += strlen($m[0]);
                    if (strcasecmp('100-continue', $m[0])) {
                        $expectations[]  = $m[0];
                    }
                }
            }
        }

        if (1024 < $this->contentLength) {
            $expectations[] = '100-continue';
            $this->expect100Continue = true;
        }

        if (empty($expectations)) {
            unset($headers['expect']);
        } else {
            $headers['expect'] = implode(',', $expectations);
        }
    }
    protected function writeBody()
    {
        if (in_array($this->request->getMethod(), self::$bodyDisallowed)
            || 0 == $this->contentLength
        ) {
            return;
        }

        $position   = 0;
        $bufferSize = $this->request->getConfig('buffer_size');
        $headers    = $this->request->getHeaders();
        $chunked    = isset($headers['transfer-encoding']);
        while ($position < $this->contentLength) {
            if (is_string($this->requestBody)) {
                $str = substr($this->requestBody, $position, $bufferSize);
            } elseif (is_resource($this->requestBody)) {
                $str = fread($this->requestBody, $bufferSize);
            } else {
                $str = $this->requestBody->read($bufferSize);
            }
            if (!$chunked) {
                $this->socket->write($str);
            } else {
                $this->socket->write(dechex(strlen($str)) . "\r\n{$str}\r\n");
            }
            $this->request->setLastEvent('sentBodyPart', strlen($str));
            $position += strlen($str);
        }
        if ($chunked) {
            $this->socket->write("0\r\n\r\n");
        }
        $this->request->setLastEvent('sentBody', $this->contentLength);
    }
    protected function readResponse()
    {
        $bufferSize = $this->request->getConfig('buffer_size');
        // ...the client SHOULD NOT wait for an indefinite period before sending the request body
        $timeout    = $this->expect100Continue ? 1 : null;

        do {
            try {
                $response = new HTTP_Request2_Response(
                    $this->socket->readLine($bufferSize, $timeout), true, $this->request->getUrl()
                );
                do {
                    $headerLine = $this->socket->readLine($bufferSize);
                    $response->parseHeaderLine($headerLine);
                } while ('' != $headerLine);

            } catch (HTTP_Request2_MessageException $e) {
                if (HTTP_Request2_Exception::TIMEOUT === $e->getCode()
                    && $this->expect100Continue
                ) {
                    return null;
                }
                throw $e;
            }
            if ($this->expect100Continue && 100 == $response->getStatus()) {
                return $response;
            }
        } while (in_array($response->getStatus(), array(100, 101)));

        $this->request->setLastEvent('receivedHeaders', $response);
        if (HTTP_Request2::METHOD_HEAD == $this->request->getMethod()
            || (HTTP_Request2::METHOD_CONNECT == $this->request->getMethod()
                && 200 <= $response->getStatus() && 300 > $response->getStatus())
            || in_array($response->getStatus(), array(204, 304))
        ) {
            return $response;
        }

        $chunked = 'chunked' == $response->getHeader('transfer-encoding');
        $length  = $response->getHeader('content-length');
        $hasBody = false;
        // 3. ... If a message is received with both a
        // the latter MUST be ignored.
        $toRead  = ($chunked || null === $length)? null: $length;
        $this->chunkLength = 0;

        if ($chunked || null === $length || 0 < intval($length)) {
            while (!$this->socket->eof() && (is_null($toRead) || 0 < $toRead)) {
                if ($chunked) {
                    $data = $this->readChunked($bufferSize);
                } elseif (is_null($toRead)) {
                    $data = $this->socket->read($bufferSize);
                } else {
                    $data    = $this->socket->read(min($toRead, $bufferSize));
                    $toRead -= strlen($data);
                }
                if ('' == $data && (!$this->chunkLength || $this->socket->eof())) {
                    break;
                }

                $hasBody = true;
                if ($this->request->getConfig('store_body')) {
                    $response->appendBody($data);
                }
                if (!in_array($response->getHeader('content-encoding'), array('identity', null))) {
                    $this->request->setLastEvent('receivedEncodedBodyPart', $data);
                } else {
                    $this->request->setLastEvent('receivedBodyPart', $data);
                }
            }
        }
        if (0 !== $this->chunkLength || null !== $toRead && $toRead > 0) {
            $this->request->setLastEvent(
                'warning', 'transfer closed with outstanding read data remaining'
            );
        }

        if ($hasBody) {
            $this->request->setLastEvent('receivedBody', $response);
        }
        return $response;
    }
    protected function readChunked($bufferSize)
    {
        if (0 == $this->chunkLength) {
            $line = $this->socket->readLine($bufferSize);
            if ('' === $line && $this->socket->eof()) {
                $this->chunkLength = -1; // indicate missing chunk
                return '';

            } elseif (!preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                throw new HTTP_Request2_MessageException(
                    "Cannot decode chunked response, invalid chunk length '{$line}'",
                    HTTP_Request2_Exception::DECODE_ERROR
                );

            } else {
                $this->chunkLength = hexdec($matches[1]);
                if (0 == $this->chunkLength) {
                    $this->socket->readLine($bufferSize);
                    return '';
                }
            }
        }
        $data = $this->socket->read(min($this->chunkLength, $bufferSize));
        $this->chunkLength -= strlen($data);
        if (0 == $this->chunkLength) {
            $this->socket->readLine($bufferSize); // Trailing CRLF
        }
        return $data;
    }
}

?>
