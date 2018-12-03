<?php
/**
 * HTTP_Request2_Adapter_Curl
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2/Adapter.php';
class HTTP_Request2_Adapter_Curl extends HTTP_Request2_Adapter
{
    protected static $headerMap = array(
        'accept-encoding' => CURLOPT_ENCODING,
        'cookie'          => CURLOPT_COOKIE,
        'referer'         => CURLOPT_REFERER,
        'user-agent'      => CURLOPT_USERAGENT
    );
    protected static $sslContextMap = array(
        'ssl_verify_peer' => CURLOPT_SSL_VERIFYPEER,
        'ssl_cafile'      => CURLOPT_CAINFO,
        'ssl_capath'      => CURLOPT_CAPATH,
        'ssl_local_cert'  => CURLOPT_SSLCERT,
        'ssl_passphrase'  => CURLOPT_SSLCERTPASSWD
    );
    protected static $errorMap = array(
        CURLE_UNSUPPORTED_PROTOCOL  => array('HTTP_Request2_MessageException',
                                             HTTP_Request2_Exception::NON_HTTP_REDIRECT),
        CURLE_COULDNT_RESOLVE_PROXY => array('HTTP_Request2_ConnectionException'),
        CURLE_COULDNT_RESOLVE_HOST  => array('HTTP_Request2_ConnectionException'),
        CURLE_COULDNT_CONNECT       => array('HTTP_Request2_ConnectionException'),
        CURLE_WRITE_ERROR           => array('HTTP_Request2_MessageException',
                                             HTTP_Request2_Exception::NON_HTTP_REDIRECT),
        CURLE_OPERATION_TIMEOUTED   => array('HTTP_Request2_MessageException',
                                             HTTP_Request2_Exception::TIMEOUT),
        CURLE_HTTP_RANGE_ERROR      => array('HTTP_Request2_MessageException'),
        CURLE_SSL_CONNECT_ERROR     => array('HTTP_Request2_ConnectionException'),
        CURLE_LIBRARY_NOT_FOUND     => array('HTTP_Request2_LogicException',
                                             HTTP_Request2_Exception::MISCONFIGURATION),
        CURLE_FUNCTION_NOT_FOUND    => array('HTTP_Request2_LogicException',
                                             HTTP_Request2_Exception::MISCONFIGURATION),
        CURLE_ABORTED_BY_CALLBACK   => array('HTTP_Request2_MessageException',
                                             HTTP_Request2_Exception::NON_HTTP_REDIRECT),
        CURLE_TOO_MANY_REDIRECTS    => array('HTTP_Request2_MessageException',
                                             HTTP_Request2_Exception::TOO_MANY_REDIRECTS),
        CURLE_SSL_PEER_CERTIFICATE  => array('HTTP_Request2_ConnectionException'),
        CURLE_GOT_NOTHING           => array('HTTP_Request2_MessageException'),
        CURLE_SSL_ENGINE_NOTFOUND   => array('HTTP_Request2_LogicException',
                                             HTTP_Request2_Exception::MISCONFIGURATION),
        CURLE_SSL_ENGINE_SETFAILED  => array('HTTP_Request2_LogicException',
                                             HTTP_Request2_Exception::MISCONFIGURATION),
        CURLE_SEND_ERROR            => array('HTTP_Request2_MessageException'),
        CURLE_RECV_ERROR            => array('HTTP_Request2_MessageException'),
        CURLE_SSL_CERTPROBLEM       => array('HTTP_Request2_LogicException',
                                             HTTP_Request2_Exception::INVALID_ARGUMENT),
        CURLE_SSL_CIPHER            => array('HTTP_Request2_ConnectionException'),
        CURLE_SSL_CACERT            => array('HTTP_Request2_ConnectionException'),
        CURLE_BAD_CONTENT_ENCODING  => array('HTTP_Request2_MessageException'),
    );
    protected $response;
    protected $eventSentHeaders = false;
    protected $eventReceivedHeaders = false;
    protected $eventSentBody = false;
    protected $position = 0;
    protected $lastInfo;
    protected static function wrapCurlError($ch)
    {
        $nativeCode = curl_errno($ch);
        $message    = 'Curl error: ' . curl_error($ch);
        if (!isset(self::$errorMap[$nativeCode])) {
            return new HTTP_Request2_Exception($message, 0, $nativeCode);
        } else {
            $class = self::$errorMap[$nativeCode][0];
            $code  = empty(self::$errorMap[$nativeCode][1])
                     ? 0 : self::$errorMap[$nativeCode][1];
            return new $class($message, $code, $nativeCode);
        }
    }
    public function sendRequest(HTTP_Request2 $request)
    {
        if (!extension_loaded('curl')) {
            throw new HTTP_Request2_LogicException(
                'cURL extension not available', HTTP_Request2_Exception::MISCONFIGURATION
            );
        }

        $this->request              = $request;
        $this->response             = null;
        $this->position             = 0;
        $this->eventSentHeaders     = false;
        $this->eventReceivedHeaders = false;
        $this->eventSentBody        = false;

        try {
            if (false === curl_exec($ch = $this->createCurlHandle())) {
                $e = self::wrapCurlError($ch);
            }
        } catch (Exception $e) {
        }
        if (isset($ch)) {
            $this->lastInfo = curl_getinfo($ch);
            if (CURLE_OK !== curl_errno($ch)) {
                $this->request->setLastEvent('warning', curl_error($ch));
            }
            curl_close($ch);
        }

        $response = $this->response;
        unset($this->request, $this->requestBody, $this->response);

        if (!empty($e)) {
            throw $e;
        }

        if ($jar = $request->getCookieJar()) {
            $jar->addCookiesFromResponse($response);
        }

        if (0 < $this->lastInfo['size_download']) {
            $request->setLastEvent('receivedBody', $response);
        }
        return $response;
    }
    public function getInfo()
    {
        return $this->lastInfo;
    }
    protected function createCurlHandle()
    {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_HEADERFUNCTION => array($this, 'callbackWriteHeader'),
            CURLOPT_WRITEFUNCTION  => array($this, 'callbackWriteBody'),
            CURLOPT_BUFFERSIZE     => $this->request->getConfig('buffer_size'),
            CURLOPT_CONNECTTIMEOUT => $this->request->getConfig('connect_timeout'),
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_URL            => $this->request->getUrl()->getUrl()
        ));
        if (!$this->request->getConfig('follow_redirects')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        } else {
            if (!@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true)) {
                throw new HTTP_Request2_LogicException(
                    'Redirect support in curl is unavailable due to open_basedir or safe_mode setting',
                    HTTP_Request2_Exception::MISCONFIGURATION
                );
            }
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->request->getConfig('max_redirects'));
            if (defined('CURLOPT_REDIR_PROTOCOLS')) {
                curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            }
            if ($this->request->getConfig('strict_redirects') && defined('CURLOPT_POSTREDIR')) {
                curl_setopt($ch, CURLOPT_POSTREDIR, 3);
            }
        }
        if ($ip = $this->request->getConfig('local_ip')) {
            curl_setopt($ch, CURLOPT_INTERFACE, $ip);
        }
        if ($timeout = $this->request->getConfig('timeout')) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        switch ($this->request->getConfig('protocol_version')) {
        case '1.0':
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            break;
        case '1.1':
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
        switch ($this->request->getMethod()) {
        case HTTP_Request2::METHOD_GET:
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            break;
        case HTTP_Request2::METHOD_POST:
            curl_setopt($ch, CURLOPT_POST, true);
            break;
        case HTTP_Request2::METHOD_HEAD:
            curl_setopt($ch, CURLOPT_NOBODY, true);
            break;
        case HTTP_Request2::METHOD_PUT:
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            break;
        default:
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->request->getMethod());
        }
        if ($host = $this->request->getConfig('proxy_host')) {
            if (!($port = $this->request->getConfig('proxy_port'))) {
                throw new HTTP_Request2_LogicException(
                    'Proxy port not provided', HTTP_Request2_Exception::MISSING_VALUE
                );
            }
            curl_setopt($ch, CURLOPT_PROXY, $host . ':' . $port);
            if ($user = $this->request->getConfig('proxy_user')) {
                curl_setopt(
                    $ch, CURLOPT_PROXYUSERPWD,
                    $user . ':' . $this->request->getConfig('proxy_password')
                );
                switch ($this->request->getConfig('proxy_auth_scheme')) {
                case HTTP_Request2::AUTH_BASIC:
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    break;
                case HTTP_Request2::AUTH_DIGEST:
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_DIGEST);
                }
            }
            if ($type = $this->request->getConfig('proxy_type')) {
                switch ($type) {
                case 'http':
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    break;
                case 'socks5':
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    break;
                default:
                    throw new HTTP_Request2_NotImplementedException(
                        "Proxy type '{$type}' is not supported"
                    );
                }
            }
        }
        if ($auth = $this->request->getAuth()) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['password']);
            switch ($auth['scheme']) {
            case HTTP_Request2::AUTH_BASIC:
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                break;
            case HTTP_Request2::AUTH_DIGEST:
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            }
        }
        foreach ($this->request->getConfig() as $name => $value) {
            if ('ssl_verify_host' == $name && null !== $value) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $value? 2: 0);
            } elseif (isset(self::$sslContextMap[$name]) && null !== $value) {
                curl_setopt($ch, self::$sslContextMap[$name], $value);
            }
        }

        $headers = $this->request->getHeaders();
        if (!isset($headers['accept-encoding'])) {
            $headers['accept-encoding'] = '';
        }

        if (($jar = $this->request->getCookieJar())
            && ($cookies = $jar->getMatching($this->request->getUrl(), true))
        ) {
            $headers['cookie'] = (empty($headers['cookie'])? '': $headers['cookie'] . '; ') . $cookies;
        }
        foreach (self::$headerMap as $name => $option) {
            if (isset($headers[$name])) {
                curl_setopt($ch, $option, $headers[$name]);
                unset($headers[$name]);
            }
        }

        $this->calculateRequestLength($headers);
        if (isset($headers['content-length']) || isset($headers['transfer-encoding'])) {
            $this->workaroundPhpBug47204($ch, $headers);
        }
        $headersFmt = array();
        foreach ($headers as $name => $value) {
            $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
            $headersFmt[]  = $canonicalName . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersFmt);

        return $ch;
    }
    protected function workaroundPhpBug47204($ch, &$headers)
    {
        // also apply workaround only for POSTs, othrerwise we get
        if (!$this->request->getConfig('follow_redirects')
            && (!($auth = $this->request->getAuth())
                || HTTP_Request2::AUTH_DIGEST != $auth['scheme'])
            || HTTP_Request2::METHOD_POST !== $this->request->getMethod()
        ) {
            curl_setopt($ch, CURLOPT_READFUNCTION, array($this, 'callbackReadBody'));

        } else {
            if ($this->requestBody instanceof HTTP_Request2_MultipartBody) {
                $this->requestBody = $this->requestBody->__toString();

            } elseif (is_resource($this->requestBody)) {
                $fp = $this->requestBody;
                $this->requestBody = '';
                while (!feof($fp)) {
                    $this->requestBody .= fread($fp, 16384);
                }
            }
            unset($headers['content-length']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        }
    }
    protected function callbackReadBody($ch, $fd, $length)
    {
        if (!$this->eventSentHeaders) {
            $this->request->setLastEvent(
                'sentHeaders', curl_getinfo($ch, CURLINFO_HEADER_OUT)
            );
            $this->eventSentHeaders = true;
        }
        if (in_array($this->request->getMethod(), self::$bodyDisallowed)
            || 0 == $this->contentLength || $this->position >= $this->contentLength
        ) {
            return '';
        }
        if (is_string($this->requestBody)) {
            $string = substr($this->requestBody, $this->position, $length);
        } elseif (is_resource($this->requestBody)) {
            $string = fread($this->requestBody, $length);
        } else {
            $string = $this->requestBody->read($length);
        }
        $this->request->setLastEvent('sentBodyPart', strlen($string));
        $this->position += strlen($string);
        return $string;
    }
    protected function callbackWriteHeader($ch, $string)
    {
        if (!$this->eventSentHeaders
            // but don't bother with 100-Continue responses (bug #15785)
            || $this->eventReceivedHeaders && $this->response->getStatus() >= 200
        ) {
            $this->request->setLastEvent(
                'sentHeaders', curl_getinfo($ch, CURLINFO_HEADER_OUT)
            );
        }
        if (!$this->eventSentBody) {
            $upload = curl_getinfo($ch, CURLINFO_SIZE_UPLOAD);
            if ($upload > $this->position) {
                $this->request->setLastEvent(
                    'sentBodyPart', $upload - $this->position
                );
            }
            if ($upload > 0) {
                $this->request->setLastEvent('sentBody', $upload);
            }
        }
        $this->eventSentHeaders = true;
        $this->eventSentBody    = true;

        if ($this->eventReceivedHeaders || empty($this->response)) {
            $this->eventReceivedHeaders = false;
            $this->response             = new HTTP_Request2_Response(
                $string, false, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)
            );

        } else {
            $this->response->parseHeaderLine($string);
            if ('' == trim($string)) {
                if (200 <= $this->response->getStatus()) {
                    $this->request->setLastEvent('receivedHeaders', $this->response);
                }

                if ($this->request->getConfig('follow_redirects') && $this->response->isRedirect()) {
                    $redirectUrl = new Net_URL2($this->response->getHeader('location'));
                    if (!defined('CURLOPT_REDIR_PROTOCOLS') && $redirectUrl->isAbsolute()
                        && !in_array($redirectUrl->getScheme(), array('http', 'https'))
                    ) {
                        return -1;
                    }

                    if ($jar = $this->request->getCookieJar()) {
                        $jar->addCookiesFromResponse($this->response);
                        if (!$redirectUrl->isAbsolute()) {
                            $redirectUrl = $this->request->getUrl()->resolve($redirectUrl);
                        }
                        if ($cookies = $jar->getMatching($redirectUrl, true)) {
                            curl_setopt($ch, CURLOPT_COOKIE, $cookies);
                        }
                    }
                }
                $this->eventReceivedHeaders = true;
                $this->eventSentBody        = false;
            }
        }
        return strlen($string);
    }
    protected function callbackWriteBody($ch, $string)
    {
        // response doesn't start with proper HTTP status line (see bug #15716)
        if (empty($this->response)) {
            throw new HTTP_Request2_MessageException(
                "Malformed response: {$string}",
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        }
        if ($this->request->getConfig('store_body')) {
            $this->response->appendBody($string);
        }
        $this->request->setLastEvent('receivedBodyPart', $string);
        return strlen($string);
    }
}
?>
