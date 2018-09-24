<?php
/**
 * Request2
 *
 * @package WP2Static
 */

if (!class_exists('Net_URL2', true)) {
    require_once 'URL2.php';
}
require_once 'Request2/Exception.php';
require_once 'Request2/Adapter/Socket.php';
class HTTP_Request2 implements SplSubject
{
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
    const AUTH_BASIC  = 'basic';
    const AUTH_DIGEST = 'digest';
    const REGEXP_INVALID_TOKEN = '![\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]!';
    const REGEXP_INVALID_COOKIE = '/[\s,;]/';
    private static $_fileinfoDb;
    protected $observers = array();
    protected $url;
    protected $method = self::METHOD_GET;
    protected $auth;
    protected $headers = array();
    protected $config = array(
        'adapter'           => 'HTTP_Request2_Adapter_Socket',
        'connect_timeout'   => 10,
        'timeout'           => 0,
        'use_brackets'      => true,
        'protocol_version'  => '1.1',
        'buffer_size'       => 16384,
        'store_body'        => true,
        'local_ip'          => null,

        'proxy_host'        => '',
        'proxy_port'        => '',
        'proxy_user'        => '',
        'proxy_password'    => '',
        'proxy_auth_scheme' => self::AUTH_BASIC,
        'proxy_type'        => 'http',

        'ssl_verify_peer'   => true,
        'ssl_verify_host'   => true,
        'ssl_cafile'        => null,
        'ssl_capath'        => null,
        'ssl_local_cert'    => null,
        'ssl_passphrase'    => null,

        'digest_compat_ie'  => false,

        'follow_redirects'  => false,
        'max_redirects'     => 5,
        'strict_redirects'  => false
    );
    protected $lastEvent = array(
        'name' => 'start',
        'data' => null
    );
    protected $body = '';
    protected $postParams = array();
    protected $uploads = array();
    protected $adapter;
    protected $cookieJar = null;
    public function __construct(
        $url = null, $method = self::METHOD_GET, array $config = array()
    ) {
        $this->setConfig($config);
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($method)) {
            $this->setMethod($method);
        }
        $this->setHeader(
            'user-agent', 'HTTP_Request2/@package_version@ ' .
            '(http://pear.php.net/package/http_request2) PHP/' . phpversion()
        );
    }
    public function setUrl($url)
    {
        if (is_string($url)) {
            $url = new Net_URL2(
                $url, array(Net_URL2::OPTION_USE_BRACKETS => $this->config['use_brackets'])
            );
        }
        if (!$url instanceof Net_URL2) {
            throw new HTTP_Request2_LogicException(
                'Parameter is not a valid HTTP URL',
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        if ($url->getUserinfo()) {
            $username = $url->getUser();
            $password = $url->getPassword();
            $this->setAuth(rawurldecode($username), $password? rawurldecode($password): '');
            $url->setUserinfo('');
        }
        if ('' == $url->getPath()) {
            $url->setPath('/');
        }
        $this->url = $url;

        return $this;
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function setMethod($method)
    {
        if (preg_match(self::REGEXP_INVALID_TOKEN, $method)) {
            throw new HTTP_Request2_LogicException(
                "Invalid request method '{$method}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        $this->method = $method;

        return $this;
    }
    public function getMethod()
    {
        return $this->method;
    }
    public function setConfig($nameOrConfig, $value = null)
    {
        if (is_array($nameOrConfig)) {
            foreach ($nameOrConfig as $name => $value) {
                $this->setConfig($name, $value);
            }

        } elseif ('proxy' == $nameOrConfig) {
            $url = new Net_URL2($value);
            $this->setConfig(array(
                'proxy_type'     => $url->getScheme(),
                'proxy_host'     => $url->getHost(),
                'proxy_port'     => $url->getPort(),
                'proxy_user'     => rawurldecode($url->getUser()),
                'proxy_password' => rawurldecode($url->getPassword())
            ));

        } else {
            if (!array_key_exists($nameOrConfig, $this->config)) {
                throw new HTTP_Request2_LogicException(
                    "Unknown configuration parameter '{$nameOrConfig}'",
                    HTTP_Request2_Exception::INVALID_ARGUMENT
                );
            }
            $this->config[$nameOrConfig] = $value;
        }

        return $this;
    }
    public function getConfig($name = null)
    {
        if (null === $name) {
            return $this->config;
        } elseif (!array_key_exists($name, $this->config)) {
            throw new HTTP_Request2_LogicException(
                "Unknown configuration parameter '{$name}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        return $this->config[$name];
    }
    public function setAuth($user, $password = '', $scheme = self::AUTH_BASIC)
    {
        if (empty($user)) {
            $this->auth = null;
        } else {
            $this->auth = array(
                'user'     => (string)$user,
                'password' => (string)$password,
                'scheme'   => $scheme
            );
        }

        return $this;
    }
    public function getAuth()
    {
        return $this->auth;
    }
    public function setHeader($name, $value = null, $replace = true)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (is_string($k)) {
                    $this->setHeader($k, $v, $replace);
                } else {
                    $this->setHeader($v, null, $replace);
                }
            }
        } else {
            if (null === $value && strpos($name, ':')) {
                list($name, $value) = array_map('trim', explode(':', $name, 2));
            }
            if (preg_match(self::REGEXP_INVALID_TOKEN, $name)) {
                throw new HTTP_Request2_LogicException(
                    "Invalid header name '{$name}'",
                    HTTP_Request2_Exception::INVALID_ARGUMENT
                );
            }
            $name = strtolower($name);
            if (null === $value) {
                unset($this->headers[$name]);

            } else {
                if (is_array($value)) {
                    $value = implode(', ', array_map('trim', $value));
                } elseif (is_string($value)) {
                    $value = trim($value);
                }
                if (!isset($this->headers[$name]) || $replace) {
                    $this->headers[$name] = $value;
                } else {
                    $this->headers[$name] .= ', ' . $value;
                }
            }
        }

        return $this;
    }
    public function getHeaders()
    {
        return $this->headers;
    }
    public function addCookie($name, $value)
    {
        if (!empty($this->cookieJar)) {
            $this->cookieJar->store(
                array('name' => $name, 'value' => $value), $this->url
            );

        } else {
            $cookie = $name . '=' . $value;
            if (preg_match(self::REGEXP_INVALID_COOKIE, $cookie)) {
                throw new HTTP_Request2_LogicException(
                    "Invalid cookie: '{$cookie}'",
                    HTTP_Request2_Exception::INVALID_ARGUMENT
                );
            }
            $cookies = empty($this->headers['cookie'])? '': $this->headers['cookie'] . '; ';
            $this->setHeader('cookie', $cookies . $cookie);
        }

        return $this;
    }
    public function setBody($body, $isFilename = false)
    {
        if (!$isFilename && !is_resource($body)) {
            if (!$body instanceof HTTP_Request2_MultipartBody) {
                $this->body = (string)$body;
            } else {
                $this->body = $body;
            }
        } else {
            $fileData = $this->fopenWrapper($body, empty($this->headers['content-type']));
            $this->body = $fileData['fp'];
            if (empty($this->headers['content-type'])) {
                $this->setHeader('content-type', $fileData['type']);
            }
        }
        $this->postParams = $this->uploads = array();

        return $this;
    }
    public function getBody()
    {
        if (self::METHOD_POST == $this->method
            && (!empty($this->postParams) || !empty($this->uploads))
        ) {
            if (0 === strpos($this->headers['content-type'], 'application/x-www-form-urlencoded')) {
                $body = http_build_query($this->postParams, '', '&');
                if (!$this->getConfig('use_brackets')) {
                    $body = preg_replace('/%5B\d+%5D=/', '=', $body);
                }
                return str_replace('%7E', '~', $body);

            } elseif (0 === strpos($this->headers['content-type'], 'multipart/form-data')) {
                require_once 'Request2/MultipartBody.php';
                return new HTTP_Request2_MultipartBody(
                    $this->postParams, $this->uploads, $this->getConfig('use_brackets')
                );
            }
        }
        return $this->body;
    }
    public function addUpload(
        $fieldName, $filename, $sendFilename = null, $contentType = null
    ) {
        if (!is_array($filename)) {
            $fileData = $this->fopenWrapper($filename, empty($contentType));
            $this->uploads[$fieldName] = array(
                'fp'        => $fileData['fp'],
                'filename'  => !empty($sendFilename)? $sendFilename
                                :(is_string($filename)? basename($filename): 'anonymous.blob') ,
                'size'      => $fileData['size'],
                'type'      => empty($contentType)? $fileData['type']: $contentType
            );
        } else {
            $fps = $names = $sizes = $types = array();
            foreach ($filename as $f) {
                if (!is_array($f)) {
                    $f = array($f);
                }
                $fileData = $this->fopenWrapper($f[0], empty($f[2]));
                $fps[]   = $fileData['fp'];
                $names[] = !empty($f[1])? $f[1]
                            :(is_string($f[0])? basename($f[0]): 'anonymous.blob');
                $sizes[] = $fileData['size'];
                $types[] = empty($f[2])? $fileData['type']: $f[2];
            }
            $this->uploads[$fieldName] = array(
                'fp' => $fps, 'filename' => $names, 'size' => $sizes, 'type' => $types
            );
        }
        if (empty($this->headers['content-type'])
            || 'application/x-www-form-urlencoded' == $this->headers['content-type']
        ) {
            $this->setHeader('content-type', 'multipart/form-data');
        }

        return $this;
    }
    public function addPostParameter($name, $value = null)
    {
        if (!is_array($name)) {
            $this->postParams[$name] = $value;
        } else {
            foreach ($name as $k => $v) {
                $this->addPostParameter($k, $v);
            }
        }
        if (empty($this->headers['content-type'])) {
            $this->setHeader('content-type', 'application/x-www-form-urlencoded');
        }

        return $this;
    }
    public function attach(SplObserver $observer)
    {
        foreach ($this->observers as $attached) {
            if ($attached === $observer) {
                return;
            }
        }
        $this->observers[] = $observer;
    }
    public function detach(SplObserver $observer)
    {
        foreach ($this->observers as $key => $attached) {
            if ($attached === $observer) {
                unset($this->observers[$key]);
                return;
            }
        }
    }
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
    public function setLastEvent($name, $data = null)
    {
        $this->lastEvent = array(
            'name' => $name,
            'data' => $data
        );
        $this->notify();
    }
    public function getLastEvent()
    {
        return $this->lastEvent;
    }
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            if (!class_exists($adapter, false)) {
                if (false === strpos($adapter, '_')) {
                    $adapter = 'HTTP_Request2_Adapter_' . ucfirst($adapter);
                }
                if (!class_exists($adapter, false)
                    && preg_match('/^HTTP_Request2_Adapter_([a-zA-Z0-9]+)$/', $adapter)
                ) {
                    include_once str_replace('_', DIRECTORY_SEPARATOR, $adapter) . '.php';
                }
                if (!class_exists($adapter, false)) {
                    throw new HTTP_Request2_LogicException(
                        "Class {$adapter} not found",
                        HTTP_Request2_Exception::MISSING_VALUE
                    );
                }
            }
            $adapter = new $adapter;
        }
        if (!$adapter instanceof HTTP_Request2_Adapter) {
            throw new HTTP_Request2_LogicException(
                'Parameter is not a HTTP request adapter',
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        $this->adapter = $adapter;

        return $this;
    }
    public function setCookieJar($jar = true)
    {
        if (!class_exists('HTTP_Request2_CookieJar', false)) {
            require_once 'Request2/CookieJar.php';
        }

        if ($jar instanceof HTTP_Request2_CookieJar) {
            $this->cookieJar = $jar;
        } elseif (true === $jar) {
            $this->cookieJar = new HTTP_Request2_CookieJar();
        } elseif (!$jar) {
            $this->cookieJar = null;
        } else {
            throw new HTTP_Request2_LogicException(
                'Invalid parameter passed to setCookieJar()',
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }

        return $this;
    }
    public function getCookieJar()
    {
        return $this->cookieJar;
    }
    public function send()
    {
        if (!$this->url instanceof Net_URL2
            || !$this->url->isAbsolute()
            || !in_array(strtolower($this->url->getScheme()), array('https', 'http'))
        ) {
            throw new HTTP_Request2_LogicException(
                'HTTP_Request2 needs an absolute HTTP(S) request URL, '
                . ($this->url instanceof Net_URL2
                   ? "'" . $this->url->__toString() . "'" : 'none')
                . ' given',
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        if (empty($this->adapter)) {
            $this->setAdapter($this->getConfig('adapter'));
        }
        // processing; see bug #4543. Don't use ini_get() here; see bug #16440.
        if ($magicQuotes = get_magic_quotes_runtime()) {
            set_magic_quotes_runtime(false);
        }
        // strlen() and substr(); see bug #1781, bug #10605
        if (extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload'))) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('8bit');
        }

        try {
            $response = $this->adapter->sendRequest($this);
        } catch (Exception $e) {
        }
        if ($magicQuotes) {
            set_magic_quotes_runtime(true);
        }
        if (!empty($oldEncoding)) {
            mb_internal_encoding($oldEncoding);
        }
        if (!empty($e)) {
            throw $e;
        }
        return $response;
    }
    protected function fopenWrapper($file, $detectType = false)
    {
        if (!is_string($file) && !is_resource($file)) {
            throw new HTTP_Request2_LogicException(
                "Filename or file pointer resource expected",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        $fileData = array(
            'fp'   => is_string($file)? null: $file,
            'type' => 'application/octet-stream',
            'size' => 0
        );
        if (is_string($file)) {
            if (!($fileData['fp'] = @fopen($file, 'rb'))) {
                $error = error_get_last();
                throw new HTTP_Request2_LogicException(
                    $error['message'], HTTP_Request2_Exception::READ_ERROR
                );
            }
            if ($detectType) {
                $fileData['type'] = self::detectMimeType($file);
            }
        }
        if (!($stat = fstat($fileData['fp']))) {
            throw new HTTP_Request2_LogicException(
                "fstat() call failed", HTTP_Request2_Exception::READ_ERROR
            );
        }
        $fileData['size'] = $stat['size'];

        return $fileData;
    }
    protected static function detectMimeType($filename)
    {
        if (function_exists('finfo_open')) {
            if (!isset(self::$_fileinfoDb)) {
                self::$_fileinfoDb = @finfo_open(FILEINFO_MIME);
            }
            if (self::$_fileinfoDb) {
                $info = finfo_file(self::$_fileinfoDb, $filename);
            }
        }
        if (empty($info) && function_exists('mime_content_type')) {
            $info = mime_content_type($filename);
        }
        return empty($info)? 'application/octet-stream': $info;
    }
}
?>
