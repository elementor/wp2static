<?php
/**
 * Socket-based adapter for HTTP_Request2
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/** Base class for HTTP_Request2 adapters */
require_once 'HTTP/Request2/Adapter.php';

/** Socket wrapper class */
require_once 'HTTP/Request2/SocketWrapper.php';

/**
 * Socket-based adapter for HTTP_Request2
 *
 * This adapter uses only PHP sockets and will work on almost any PHP
 * environment. Code is based on original HTTP_Request PEAR package.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_Adapter_Socket extends HTTP_Request2_Adapter
{
    /**
     * Regular expression for 'token' rule from RFC 2616
     */
    const REGEXP_TOKEN = '[^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+';

    /**
     * Regular expression for 'quoted-string' rule from RFC 2616
     */
    const REGEXP_QUOTED_STRING = '"(?>[^"\\\\]+|\\\\.)*"';

    /**
     * Connected sockets, needed for Keep-Alive support
     * @var  array
     * @see  connect()
     */
    protected static $sockets = array();

    /**
     * Data for digest authentication scheme
     *
     * The keys for the array are URL prefixes.
     *
     * The values are associative arrays with data (realm, nonce, nonce-count,
     * opaque...) needed for digest authentication. Stored here to prevent making
     * duplicate requests to digest-protected resources after we have already
     * received the challenge.
     *
     * @var  array
     */
    protected static $challenges = array();

    /**
     * Connected socket
     * @var  HTTP_Request2_SocketWrapper
     * @see  connect()
     */
    protected $socket;

    /**
     * Challenge used for server digest authentication
     * @var  array
     */
    protected $serverChallenge;

    /**
     * Challenge used for proxy digest authentication
     * @var  array
     */
    protected $proxyChallenge;

    /**
     * Remaining length of the current chunk, when reading chunked response
     * @var  integer
     * @see  readChunked()
     */
    protected $chunkLength = 0;

    /**
     * Remaining amount of redirections to follow
     *
     * Starts at 'max_redirects' configuration parameter and is reduced on each
     * subsequent redirect. An Exception will be thrown once it reaches zero.
     *
     * @var  integer
     */
    protected $redirectCountdown = null;

    /**
     * Whether to wait for "100 Continue" response before sending request body
     * @var bool
     */
    protected $expect100Continue = false;

    /**
     * Sends request to the remote server and returns its response
     *
     * @param HTTP_Request2 $request HTTP request message
     *
     * @return   HTTP_Request2_Response
     * @throws   HTTP_Request2_Exception
     */
    public function sendRequest(HTTP_Request2 $request)
    {
        $this->request = $request;

        try {
            $keepAlive = $this->connect();
            $headers   = $this->prepareHeaders();
            $this->socket->write($headers);
            // provide request headers to the observer, see request #7633
            $this->request->setLastEvent('sentHeaders', $headers);

            if (!$this->expect100Continue) {
                $this->writeBody();
                $response = $this->readResponse();

            } else {
                $response = $this->readResponse();
                if (!$response || 100 == $response->getStatus()) {
                    $this->expect100Continue = false;
                    // either got "100 Continue" or timed out -> send body
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

    /**
     * Connects to the remote server
     *
     * @return   bool    whether the connection can be persistent
     * @throws   HTTP_Request2_Exception
     */
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

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive
        // connection token to a proxy server...
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

        // Use global request timeout if given, see feature requests #5735, #8964
        if ($timeout = $this->request->getConfig('timeout')) {
            $deadline = time() + $timeout;
        } else {
            $deadline = null;
        }

        // Changing SSL context options after connection is established does *not*
        // work, we need a new connection if options change
        $remote    = ((!$secure || $httpProxy || $socksProxy)? 'tcp://': 'tls://')
                     . $host . ':' . $port;
        $socketKey = $remote . (
                        ($secure && $httpProxy || $socksProxy)
                        ? "->{$reqHost}:{$reqPort}" : ''
                     ) . (empty($options)? '': ':' . serialize($options));
        unset($this->socket);

        // We use persistent connections and have a connected socket?
        // Ensure that the socket is still connected, see bug #16149
        if ($keepAlive && !empty(self::$sockets[$socketKey])
            && !self::$sockets[$socketKey]->eof()
        ) {
            $this->socket =& self::$sockets[$socketKey];

        } else {
            if ($socksProxy) {
                require_once 'HTTP/Request2/SOCKS5.php';

                $this->socket = new HTTP_Request2_SOCKS5(
                    $remote, $this->request->getConfig('connect_timeout'),
                    $options, $this->request->getConfig('proxy_user'),
                    $this->request->getConfig('proxy_password')
                );
                // handle request timeouts ASAP
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

    /**
     * Establishes a tunnel to a secure remote server via HTTP CONNECT request
     *
     * This method will fail if 'ssl_verify_peer' is enabled. Probably because PHP
     * sees that we are connected to a proxy server (duh!) rather than the server
     * that presents its certificate.
     *
     * @link     http://tools.ietf.org/html/rfc2817#section-5.2
     * @throws   HTTP_Request2_Exception
     */
    protected function establishTunnel()
    {
        $donor   = new self;
        $connect = new HTTP_Request2(
            $this->request->getUrl(), HTTP_Request2::METHOD_CONNECT,
            array_merge($this->request->getConfig(), array('adapter' => $donor))
        );
        $response = $connect->send();
        // Need any successful (2XX) response
        if (200 > $response->getStatus() || 300 <= $response->getStatus()) {
            throw new HTTP_Request2_ConnectionException(
                'Failed to connect via HTTPS proxy. Proxy response: ' .
                $response->getStatus() . ' ' . $response->getReasonPhrase()
            );
        }
        $this->socket = $donor->socket;
        $this->socket->enableCrypto();
    }

    /**
     * Checks whether current connection may be reused or should be closed
     *
     * @param boolean                $requestKeepAlive whether connection could
     *                               be persistent in the first place
     * @param HTTP_Request2_Response $response         response object to check
     *
     * @return   boolean
     */
    protected function canKeepAlive($requestKeepAlive, HTTP_Request2_Response $response)
    {
        // Do not close socket on successful CONNECT request
        if (HTTP_Request2::METHOD_CONNECT == $this->request->getMethod()
            && 200 <= $response->getStatus() && 300 > $response->getStatus()
        ) {
            return true;
        }

        $lengthKnown = 'chunked' == strtolower($response->getHeader('transfer-encoding'))
                       || null !== $response->getHeader('content-length')
                       // no body possible for such responses, see also request #17031
                       || HTTP_Request2::METHOD_HEAD == $this->request->getMethod()
                       || in_array($response->getStatus(), array(204, 304));
        $persistent  = 'keep-alive' == strtolower($response->getHeader('connection')) ||
                       (null === $response->getHeader('connection') &&
                        '1.1' == $response->getVersion());
        return $requestKeepAlive && $lengthKnown && $persistent;
    }

    /**
     * Disconnects from the remote server
     */
    protected function disconnect()
    {
        if (!empty($this->socket)) {
            $this->socket = null;
            $this->request->setLastEvent('disconnect');
        }
    }

    /**
     * Handles HTTP redirection
     *
     * This method will throw an Exception if redirect to a non-HTTP(S) location
     * is attempted, also if number of redirects performed already is equal to
     * 'max_redirects' configuration parameter.
     *
     * @param HTTP_Request2          $request  Original request
     * @param HTTP_Request2_Response $response Response containing redirect
     *
     * @return   HTTP_Request2_Response      Response from a new location
     * @throws   HTTP_Request2_Exception
     */
    protected function handleRedirect(
        HTTP_Request2 $request, HTTP_Request2_Response $response
    ) {
        if (is_null($this->redirectCountdown)) {
            $this->redirectCountdown = $request->getConfig('max_redirects');
        }
        if (0 == $this->redirectCountdown) {
            $this->redirectCountdown = null;
            // Copying cURL behaviour
            throw new HTTP_Request2_MessageException(
                'Maximum (' . $request->getConfig('max_redirects') . ') redirects followed',
                HTTP_Request2_Exception::TOO_MANY_REDIRECTS
            );
        }
        $redirectUrl = new Net_URL2(
            $response->getHeader('location'),
            array(Net_URL2::OPTION_USE_BRACKETS => $request->getConfig('use_brackets'))
        );
        // refuse non-HTTP redirect
        if ($redirectUrl->isAbsolute()
            && !in_array($redirectUrl->getScheme(), array('http', 'https'))
        ) {
            $this->redirectCountdown = null;
            throw new HTTP_Request2_MessageException(
                'Refusing to redirect to a non-HTTP URL ' . $redirectUrl->__toString(),
                HTTP_Request2_Exception::NON_HTTP_REDIRECT
            );
        }
        // Theoretically URL should be absolute (see http://tools.ietf.org/html/rfc2616#section-14.30),
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

    /**
     * Checks whether another request should be performed with server digest auth
     *
     * Several conditions should be satisfied for it to return true:
     *   - response status should be 401
     *   - auth credentials should be set in the request object
     *   - response should contain WWW-Authenticate header with digest challenge
     *   - there is either no challenge stored for this URL or new challenge
     *     contains stale=true parameter (in other case we probably just failed
     *     due to invalid username / password)
     *
     * The method stores challenge values in $challenges static property
     *
     * @param HTTP_Request2_Response $response response to check
     *
     * @return   boolean whether another request should be performed
     * @throws   HTTP_Request2_Exception in case of unsupported challenge parameters
     */
    protected function shouldUseServerDigestAuth(HTTP_Request2_Response $response)
    {
        // no sense repeating a request if we don't have credentials
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
                // don't bother with different servers
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
                // probably credentials are invalid
                $ret = false;
            }
            self::$challenges[$prefix] =& $challenge;
        }
        return $ret;
    }

    /**
     * Checks whether another request should be performed with proxy digest auth
     *
     * Several conditions should be satisfied for it to return true:
     *   - response status should be 407
     *   - proxy auth credentials should be set in the request object
     *   - response should contain Proxy-Authenticate header with digest challenge
     *   - there is either no challenge stored for this proxy or new challenge
     *     contains stale=true parameter (in other case we probably just failed
     *     due to invalid username / password)
     *
     * The method stores challenge values in $challenges static property
     *
     * @param HTTP_Request2_Response $response response to check
     *
     * @return   boolean whether another request should be performed
     * @throws   HTTP_Request2_Exception in case of unsupported challenge parameters
     */
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

    /**
     * Extracts digest method challenge from (WWW|Proxy)-Authenticate header value
     *
     * There is a problem with implementation of RFC 2617: several of the parameters
     * are defined as quoted-string there and thus may contain backslash escaped
     * double quotes (RFC 2616, section 2.2). However, RFC 2617 defines unq(X) as
     * just value of quoted-string X without surrounding quotes, it doesn't speak
     * about removing backslash escaping.
     *
     * Now realm parameter is user-defined and human-readable, strange things
     * happen when it contains quotes:
     *   - Apache allows quotes in realm, but apparently uses realm value without
     *     backslashes for digest computation
     *   - Squid allows (manually escaped) quotes there, but it is impossible to
     *     authorize with either escaped or unescaped quotes used in digest,
     *     probably it can't parse the response (?)
     *   - Both IE and Firefox display realm value with backslashes in
     *     the password popup and apparently use the same value for digest
     *
     * HTTP_Request2 follows IE and Firefox (and hopefully RFC 2617) in
     * quoted-string handling, unfortunately that means failure to authorize
     * sometimes
     *
     * @param string $headerValue value of WWW-Authenticate or Proxy-Authenticate header
     *
     * @return   mixed   associative array with challenge parameters, false if
     *                   no challenge is present in header value
     * @throws   HTTP_Request2_NotImplementedException in case of unsupported challenge parameters
     */
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
            // section 3.2.1: Any unrecognized directive MUST be ignored.
            if (in_array($params[1][$i], $knownParams)) {
                if ('"' == substr($params[2][$i], 0, 1)) {
                    $paramsAry[$params[1][$i]] = substr($params[2][$i], 1, -1);
                } else {
                    $paramsAry[$params[1][$i]] = $params[2][$i];
                }
            }
        }
        // we only support qop=auth
        if (!empty($paramsAry['qop'])
            && !in_array('auth', array_map('trim', explode(',', $paramsAry['qop'])))
        ) {
            throw new HTTP_Request2_NotImplementedException(
                "Only 'auth' qop is currently supported in digest authentication, " .
                "server requested '{$paramsAry['qop']}'"
            );
        }
        // we only support algorithm=MD5
        if (!empty($paramsAry['algorithm']) && 'MD5' != $paramsAry['algorithm']) {
            throw new HTTP_Request2_NotImplementedException(
                "Only 'MD5' algorithm is currently supported in digest authentication, " .
                "server requested '{$paramsAry['algorithm']}'"
            );
        }

        return $paramsAry;
    }

    /**
     * Parses [Proxy-]Authentication-Info header value and updates challenge
     *
     * @param array  &$challenge  challenge to update
     * @param string $headerValue value of [Proxy-]Authentication-Info header
     *
     * @todo     validate server rspauth response
     */
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
        // for now, just update the nonce value
        if (!empty($paramsAry['nextnonce'])) {
            $challenge['nonce'] = $paramsAry['nextnonce'];
            $challenge['nc']    = 1;
        }
    }

    /**
     * Creates a value for [Proxy-]Authorization header when using digest authentication
     *
     * @param string $user       user name
     * @param string $password   password
     * @param string $url        request URL
     * @param array  &$challenge digest challenge parameters
     *
     * @return   string  value of [Proxy-]Authorization request header
     * @link     http://tools.ietf.org/html/rfc2617#section-3.2.2
     */
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

    /**
     * Adds 'Authorization' header (if needed) to request headers array
     *
     * @param array  &$headers    request headers
     * @param string $requestHost request host (needed for digest authentication)
     * @param string $requestUrl  request URL (needed for digest authentication)
     *
     * @throws   HTTP_Request2_NotImplementedException
     */
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

    /**
     * Adds 'Proxy-Authorization' header (if needed) to request headers array
     *
     * @param array  &$headers   request headers
     * @param string $requestUrl request URL (needed for digest authentication)
     *
     * @throws   HTTP_Request2_NotImplementedException
     */
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


    /**
     * Creates the string with the Request-Line and request headers
     *
     * @return   string
     * @throws   HTTP_Request2_Exception
     */
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
        // Do not overwrite explicitly set 'Host' header, see bug #16146
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

    /**
     * Adds or removes 'Expect: 100-continue' header from request headers
     *
     * Also sets the $expect100Continue property. Parsing of existing header
     * is somewhat needed due to its complex structure and due to the
     * requirement in section 8.2.3 of RFC 2616:
     * > A client MUST NOT send an Expect request-header field (section
     * > 14.20) with the "100-continue" expectation if it does not intend
     * > to send a request body.
     *
     * @param array &$headers Array of headers prepared for the request
     *
     * @throws HTTP_Request2_LogicException
     * @link http://pear.php.net/bugs/bug.php?id=19233
     * @link http://tools.ietf.org/html/rfc2616#section-8.2.3
     */
    protected function updateExpectHeader(&$headers)
    {
        $this->expect100Continue = false;
        $expectations = array();
        if (isset($headers['expect'])) {
            if ('' === $headers['expect']) {
                // empty 'Expect' header is technically invalid, so just get rid of it
                unset($headers['expect']);
                return;
            }
            // build regexp to parse the value of existing Expect header
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

    /**
     * Sends the request body
     *
     * @throws   HTTP_Request2_MessageException
     */
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
            // Provide the length of written string to the observer, request #7630
            $this->request->setLastEvent('sentBodyPart', strlen($str));
            $position += strlen($str);
        }

        // write zero-length chunk
        if ($chunked) {
            $this->socket->write("0\r\n\r\n");
        }
        $this->request->setLastEvent('sentBody', $this->contentLength);
    }

    /**
     * Reads the remote server's response
     *
     * @return   HTTP_Request2_Response
     * @throws   HTTP_Request2_Exception
     */
    protected function readResponse()
    {
        $bufferSize = $this->request->getConfig('buffer_size');
        // http://tools.ietf.org/html/rfc2616#section-8.2.3
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

        // No body possible in such responses
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
        // RFC 2616, section 4.4:
        // 3. ... If a message is received with both a
        // Transfer-Encoding header field and a Content-Length header field,
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

    /**
     * Reads a part of response body encoded with chunked Transfer-Encoding
     *
     * @param int $bufferSize buffer size to use for reading
     *
     * @return   string
     * @throws   HTTP_Request2_MessageException
     */
    protected function readChunked($bufferSize)
    {
        // at start of the next chunk?
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
                // Chunk with zero length indicates the end
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