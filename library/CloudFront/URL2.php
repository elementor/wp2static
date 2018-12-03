<?php
/**
 * Net_URL2
 *
 * @package WP2Static
 */

class Net_URL2
{
    const OPTION_STRICT = 'strict';
    const OPTION_USE_BRACKETS = 'use_brackets';
    const OPTION_DROP_SEQUENCE = 'drop_sequence';
    const OPTION_ENCODE_KEYS = 'encode_keys';
    const OPTION_SEPARATOR_INPUT = 'input_separator';
    const OPTION_SEPARATOR_OUTPUT = 'output_separator';
    private $_options = array(
        self::OPTION_STRICT           => true,
        self::OPTION_USE_BRACKETS     => true,
        self::OPTION_DROP_SEQUENCE    => true,
        self::OPTION_ENCODE_KEYS      => true,
        self::OPTION_SEPARATOR_INPUT  => '&',
        self::OPTION_SEPARATOR_OUTPUT => '&',
        );
    private $_scheme = false;
    private $_userinfo = false;
    private $_host = false;
    private $_port = false;
    private $_path = '';
    private $_query = false;
    private $_fragment = false;
    public function __construct($url, array $options = array())
    {
        foreach ($options as $optionName => $value) {
            if (array_key_exists($optionName, $this->_options)) {
                $this->_options[$optionName] = $value;
            }
        }

        $this->parseUrl($url);
    }
    public function __set($var, $arg)
    {
        $method = 'set' . $var;
        if (method_exists($this, $method)) {
            $this->$method($arg);
        }
    }
    public function __get($var)
    {
        $method = 'get' . $var;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return false;
    }
    public function getScheme()
    {
        return $this->_scheme;
    }
    public function setScheme($scheme)
    {
        $this->_scheme = $scheme;
        return $this;
    }
    public function getUser()
    {
        return $this->_userinfo !== false
            ? preg_replace('(:.*$)', '', $this->_userinfo)
            : false;
    }
    public function getPassword()
    {
        return $this->_userinfo !== false
            ? substr(strstr($this->_userinfo, ':'), 1)
            : false;
    }
    public function getUserinfo()
    {
        return $this->_userinfo;
    }
    public function setUserinfo($userinfo, $password = false)
    {
        if ($password !== false) {
            $userinfo .= ':' . $password;
        }

        if ($userinfo !== false) {
            $userinfo = $this->_encodeData($userinfo);
        }

        $this->_userinfo = $userinfo;
        return $this;
    }
    public function getHost()
    {
        return $this->_host;
    }
    public function setHost($host)
    {
        $this->_host = $host;
        return $this;
    }
    public function getPort()
    {
        return $this->_port;
    }
    public function setPort($port)
    {
        $this->_port = $port;
        return $this;
    }
    public function getAuthority()
    {
        if (false === $this->_host) {
            return false;
        }

        $authority = '';

        if (strlen($this->_userinfo)) {
            $authority .= $this->_userinfo . '@';
        }

        $authority .= $this->_host;

        if ($this->_port !== false) {
            $authority .= ':' . $this->_port;
        }

        return $authority;
    }
    public function setAuthority($authority)
    {
        $this->_userinfo = false;
        $this->_host     = false;
        $this->_port     = false;

        if ('' === $authority) {
            $this->_host = $authority;
            return $this;
        }

        if (!preg_match('(^(([^@]*)@)?(.+?)(:(\d*))?$)', $authority, $matches)) {
            return $this;
        }

        if ($matches[1]) {
            $this->_userinfo = $this->_encodeData($matches[2]);
        }

        $this->_host = $matches[3];

        if (isset($matches[5]) && strlen($matches[5])) {
            $this->_port = $matches[5];
        }
        return $this;
    }
    public function getPath()
    {
        return $this->_path;
    }
    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }
    public function getQuery()
    {
        return $this->_query;
    }
    public function setQuery($query)
    {
        $this->_query = $query;
        return $this;
    }
    public function getFragment()
    {
        return $this->_fragment;
    }
    public function setFragment($fragment)
    {
        $this->_fragment = $fragment;
        return $this;
    }
    public function getQueryVariables()
    {
        $separator   = $this->getOption(self::OPTION_SEPARATOR_INPUT);
        $encodeKeys  = $this->getOption(self::OPTION_ENCODE_KEYS);
        $useBrackets = $this->getOption(self::OPTION_USE_BRACKETS);

        $return  = array();

        for ($part = strtok($this->_query, $separator);
            strlen($part);
            $part = strtok($separator)
        ) {
            list($key, $value) = explode('=', $part, 2) + array(1 => '');

            if ($encodeKeys) {
                $key = rawurldecode($key);
            }
            $value = rawurldecode($value);

            if ($useBrackets) {
                $return = $this->_queryArrayByKey($key, $value, $return);
            } else {
                if (isset($return[$key])) {
                    $return[$key]  = (array) $return[$key];
                    $return[$key][] = $value;
                } else {
                    $return[$key] = $value;
                }
            }
        }

        return $return;
    }
    private function _queryArrayByKey($key, $value, array $array = array())
    {
        if (!strlen($key)) {
            return $array;
        }

        $offset = $this->_queryKeyBracketOffset($key);
        if ($offset === false) {
            $name = $key;
        } else {
            $name = substr($key, 0, $offset);
        }

        if (!strlen($name)) {
            return $array;
        }

        if (!$offset) {
            $array[$name] = $value;
        } else {
            $brackets = substr($key, $offset);
            if (!isset($array[$name])) {
                $array[$name] = null;
            }
            $array[$name] = $this->_queryArrayByBrackets(
                $brackets, $value, $array[$name]
            );
        }

        return $array;
    }
    private function _queryArrayByBrackets($buffer, $value, array $array = null)
    {
        $entry = &$array;

        for ($iteration = 0; strlen($buffer); $iteration++) {
            $open = $this->_queryKeyBracketOffset($buffer);
            if ($open !== 0) {
                // no bracket to parse and the value dropped.
                // as well the second exception below.
                if ($iteration) {
                    break;
                }
                throw new Exception(
                    'Net_URL2 Internal Error: '. __METHOD__ .'(): ' .
                    'Opening bracket [ must exist at offset 0'
                );
            }

            $close = strpos($buffer, ']', 1);
            if (!$close) {
                // private method and bracket pairs are checked beforehand.
                // @codeCoverageIgnoreStart
                throw new Exception(
                    'Net_URL2 Internal Error: '. __METHOD__ .'(): ' .
                    'Closing bracket ] must exist, not found'
                );
            }

            $index = substr($buffer, 1, $close - 1);
            if (strlen($index)) {
                $entry = &$entry[$index];
            } else {
                if (!is_array($entry)) {
                    $entry = array();
                }
                $entry[] = &$new;
                $entry = &$new;
                unset($new);
            }
            $buffer = substr($buffer, $close + 1);
        }

        $entry = $value;

        return $array;
    }
    private function _queryKeyBracketOffset($key)
    {
        if (false !== $open = strpos($key, '[')
            and false === strpos($key, ']', $open + 1)
        ) {
            $open = false;
        }

        return $open;
    }
    public function setQueryVariables(array $array)
    {
        if (!$array) {
            $this->_query = false;
        } else {
            $this->_query = $this->buildQuery(
                $array,
                $this->getOption(self::OPTION_SEPARATOR_OUTPUT)
            );
        }
        return $this;
    }
    public function setQueryVariable($name, $value)
    {
        $array = $this->getQueryVariables();
        $array[$name] = $value;
        $this->setQueryVariables($array);
        return $this;
    }
    public function unsetQueryVariable($name)
    {
        $array = $this->getQueryVariables();
        unset($array[$name]);
        $this->setQueryVariables($array);
    }
    public function getURL()
    {
        $url = '';

        if ($this->_scheme !== false) {
            $url .= $this->_scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority === false && strtolower($this->_scheme) === 'file') {
            $authority = '';
        }

        $url .= $this->_buildAuthorityAndPath($authority, $this->_path);

        if ($this->_query !== false) {
            $url .= '?' . $this->_query;
        }

        if ($this->_fragment !== false) {
            $url .= '#' . $this->_fragment;
        }

        return $url;
    }
    private function _buildAuthorityAndPath($authority, $path)
    {
        if ($authority === false) {
            return $path;
        }

        $terminator = ($path !== '' && $path[0] !== '/') ? '/' : '';

        return '//' . $authority . $terminator . $path;
    }
    public function __toString()
    {
        return $this->getURL();
    }
    public function getNormalizedURL()
    {
        $url = clone $this;
        $url->normalize();
        return $url->getURL();
    }
    public function normalize()
    {

        if ($this->_scheme) {
            $this->_scheme = strtolower($this->_scheme);
        }
        if ($this->_host) {
            $this->_host = strtolower($this->_host);
        }
        if ('' === $this->_port
            || $this->_port
            && $this->_scheme
            && $this->_port == getservbyname($this->_scheme, 'tcp')
        ) {
            $this->_port = false;
        }
        // Normalize percentage-encoded unreserved characters (section 6.2.2.2)
        $fields = array(&$this->_userinfo, &$this->_host, &$this->_path,
                        &$this->_query, &$this->_fragment);
        foreach ($fields as &$field) {
            if ($field !== false) {
                $field = $this->_normalize("$field");
            }
        }
        unset($field);
        $this->_path = self::removeDotSegments($this->_path);
        if (false !== $this->_host && '' === $this->_path) {
            $this->_path = '/';
        }
        if (strlen($this->getAuthority())
            && strlen($this->_path)
            && $this->_path[0] !== '/'
        ) {
            $this->_path = '/' . $this->_path;
        }
    }
    private function _normalize($mixed)
    {
        return preg_replace_callback(
            '((?:%[0-9a-fA-Z]{2})+)', array($this, '_normalizeCallback'),
            $mixed
        );
    }
    private function _normalizeCallback($matches)
    {
        return self::urlencode(urldecode($matches[0]));
    }
    public function isAbsolute()
    {
        return (bool) $this->_scheme;
    }
    public function resolve($reference)
    {
        if (!$reference instanceof Net_URL2) {
            $reference = new self($reference);
        }
        if (!$reference->_isFragmentOnly() && !$this->isAbsolute()) {
            throw new Exception(
                'Base-URL must be absolute if reference is not fragment-only'
            );
        }
        // identical to the base URI's scheme.
        if (!$this->getOption(self::OPTION_STRICT)
            && $reference->_scheme == $this->_scheme
        ) {
            $reference->_scheme = false;
        }

        $target = new self('');
        if ($reference->_scheme !== false) {
            $target->_scheme = $reference->_scheme;
            $target->setAuthority($reference->getAuthority());
            $target->_path  = self::removeDotSegments($reference->_path);
            $target->_query = $reference->_query;
        } else {
            $authority = $reference->getAuthority();
            if ($authority !== false) {
                $target->setAuthority($authority);
                $target->_path  = self::removeDotSegments($reference->_path);
                $target->_query = $reference->_query;
            } else {
                if ($reference->_path == '') {
                    $target->_path = $this->_path;
                    if ($reference->_query !== false) {
                        $target->_query = $reference->_query;
                    } else {
                        $target->_query = $this->_query;
                    }
                } else {
                    if (substr($reference->_path, 0, 1) == '/') {
                        $target->_path = self::removeDotSegments($reference->_path);
                    } else {
                        if ($this->_host !== false && $this->_path == '') {
                            $target->_path = '/' . $reference->_path;
                        } else {
                            $i = strrpos($this->_path, '/');
                            if ($i !== false) {
                                $target->_path = substr($this->_path, 0, $i + 1);
                            }
                            $target->_path .= $reference->_path;
                        }
                        $target->_path = self::removeDotSegments($target->_path);
                    }
                    $target->_query = $reference->_query;
                }
                $target->setAuthority($this->getAuthority());
            }
            $target->_scheme = $this->_scheme;
        }

        $target->_fragment = $reference->_fragment;

        return $target;
    }
    private function _isFragmentOnly()
    {
        return (
            $this->_fragment !== false
            && $this->_query === false
            && $this->_path === ''
            && $this->_port === false
            && $this->_host === false
            && $this->_userinfo === false
            && $this->_scheme === false
        );
    }
    public static function removeDotSegments($path)
    {
        $path = (string) $path;
        $output = '';
        // method
        $loopLimit = 256;
        $j = 0;
        while ('' !== $path && $j++ < $loopLimit) {
            if (substr($path, 0, 2) === './') {
                $path = substr($path, 2);
            } elseif (substr($path, 0, 3) === '../') {
                $path = substr($path, 3);
            } elseif (substr($path, 0, 3) === '/./' || $path === '/.') {
                $path = '/' . substr($path, 3);
            } elseif (substr($path, 0, 4) === '/../' || $path === '/..') {
                $path   = '/' . substr($path, 4);
                $i      = strrpos($output, '/');
                $output = $i === false ? '' : substr($output, 0, $i);
            } elseif ($path === '.' || $path === '..') {
                $path = '';
            } else {
                $i = strpos($path, '/', $path[0] === '/');
                if ($i === false) {
                    $output .= $path;
                    $path = '';
                    break;
                }
                $output .= substr($path, 0, $i);
                $path = substr($path, $i);
            }
        }

        if ($path !== '') {
            $message = sprintf(
                'Unable to remove dot segments; hit loop limit %d (left: %s)',
                $j, var_export($path, true)
            );
            trigger_error($message, E_USER_WARNING);
        }

        return $output;
    }
    public static function urlencode($string)
    {
        $encoded = rawurlencode($string);
        $encoded = str_replace('%7E', '~', $encoded);
        return $encoded;
    }
    public static function getCanonical()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('Script was not called through a webserver');
        }
        $url = new self($_SERVER['PHP_SELF']);
        $url->_scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $url->_host   = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if ($url->_scheme == 'http' && $port != 80
            || $url->_scheme == 'https' && $port != 443
        ) {
            $url->_port = $port;
        }
        return $url;
    }
    public static function getRequestedURL()
    {
        return self::getRequested()->getUrl();
    }
    public static function getRequested()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('Script was not called through a webserver');
        }
        $url = new self($_SERVER['REQUEST_URI']);
        $url->_scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $url->setAuthority($_SERVER['HTTP_HOST']);
        return $url;
    }
    public function getOption($optionName)
    {
        return isset($this->_options[$optionName])
            ? $this->_options[$optionName] : false;
    }
    protected function buildQuery(array $data, $separator, $key = null)
    {
        $query = array();
        $drop_names = (
            $this->_options[self::OPTION_DROP_SEQUENCE] === true
            && array_keys($data) === array_keys(array_values($data))
        );
        foreach ($data as $name => $value) {
            if ($this->getOption(self::OPTION_ENCODE_KEYS) === true) {
                $name = rawurlencode($name);
            }
            if ($key !== null) {
                if ($this->getOption(self::OPTION_USE_BRACKETS) === true) {
                    $drop_names && $name = '';
                    $name = $key . '[' . $name . ']';
                } else {
                    $name = $key;
                }
            }
            if (is_array($value)) {
                $query[] = $this->buildQuery($value, $separator, $name);
            } else {
                $query[] = $name . '=' . rawurlencode($value);
            }
        }
        return implode($separator, $query);
    }
    protected function parseUrl($url)
    {
        // The expression does not validate the URL but matches any string.
        preg_match(
            '(^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?)',
            $url, $matches
        );
        // are optional.
        $this->_scheme   = !empty($matches[1]) ? $matches[2] : false;
        $this->setAuthority(!empty($matches[3]) ? $matches[4] : false);
        $this->_path     = $this->_encodeData($matches[5]);
        $this->_query    = !empty($matches[6])
                           ? $this->_encodeData($matches[7])
                           : false
            ;
        $this->_fragment = !empty($matches[8]) ? $matches[9] : false;
    }
    private function _encodeData($url)
    {
        return preg_replace_callback(
            '([\x-\x20\x22\x3C\x3E\x7F-\xFF]+)',
            array($this, '_encodeCallback'), $url
        );
    }
    private function _encodeCallback(array $matches)
    {
        return rawurlencode($matches[0]);
    }
}
