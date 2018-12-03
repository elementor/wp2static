<?php
/**
 * HTTP_Request2_CookieJar
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2.php';
class HTTP_Request2_CookieJar implements Serializable
{
    protected $cookies = array();
    protected $serializeSession = false;
    protected $useList = true;
    protected $ignoreInvalid = false;
    protected static $psl = array();
    public function __construct(
        $serializeSessionCookies = false, $usePublicSuffixList = true,
        $ignoreInvalidCookies = false
    ) {
        $this->serializeSessionCookies($serializeSessionCookies);
        $this->usePublicSuffixList($usePublicSuffixList);
        $this->ignoreInvalidCookies($ignoreInvalidCookies);
    }
    protected function now()
    {
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format(DateTime::ISO8601);
    }
    protected function checkAndUpdateFields(array $cookie, Net_URL2 $setter = null)
    {
        if ($missing = array_diff(array('name', 'value'), array_keys($cookie))) {
            throw new HTTP_Request2_LogicException(
                "Cookie array should contain 'name' and 'value' fields",
                HTTP_Request2_Exception::MISSING_VALUE
            );
        }
        if (preg_match(HTTP_Request2::REGEXP_INVALID_COOKIE, $cookie['name'])) {
            throw new HTTP_Request2_LogicException(
                "Invalid cookie name: '{$cookie['name']}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        if (preg_match(HTTP_Request2::REGEXP_INVALID_COOKIE, $cookie['value'])) {
            throw new HTTP_Request2_LogicException(
                "Invalid cookie value: '{$cookie['value']}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        $cookie += array('domain' => '', 'path' => '', 'expires' => null, 'secure' => false);
        if (!empty($cookie['expires'])
            && !preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\+0000$/', $cookie['expires'])
        ) {
            try {
                $dt = new DateTime($cookie['expires']);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $cookie['expires'] = $dt->format(DateTime::ISO8601);
            } catch (Exception $e) {
                throw new HTTP_Request2_LogicException($e->getMessage());
            }
        }

        if (empty($cookie['domain']) || empty($cookie['path'])) {
            if (!$setter) {
                throw new HTTP_Request2_LogicException(
                    'Cookie misses domain and/or path component, cookie setter URL needed',
                    HTTP_Request2_Exception::MISSING_VALUE
                );
            }
            if (empty($cookie['domain'])) {
                if ($host = $setter->getHost()) {
                    $cookie['domain'] = $host;
                } else {
                    throw new HTTP_Request2_LogicException(
                        'Setter URL does not contain host part, can\'t set cookie domain',
                        HTTP_Request2_Exception::MISSING_VALUE
                    );
                }
            }
            if (empty($cookie['path'])) {
                $path = $setter->getPath();
                $cookie['path'] = empty($path)? '/': substr($path, 0, strrpos($path, '/') + 1);
            }
        }

        if ($setter && !$this->domainMatch($setter->getHost(), $cookie['domain'])) {
            throw new HTTP_Request2_MessageException(
                "Domain " . $setter->getHost() . " cannot set cookies for "
                . $cookie['domain']
            );
        }

        return $cookie;
    }
    public function store(array $cookie, Net_URL2 $setter = null)
    {
        try {
            $cookie = $this->checkAndUpdateFields($cookie, $setter);
        } catch (HTTP_Request2_Exception $e) {
            if ($this->ignoreInvalid) {
                return false;
            } else {
                throw $e;
            }
        }

        if (strlen($cookie['value'])
            && (is_null($cookie['expires']) || $cookie['expires'] > $this->now())
        ) {
            if (!isset($this->cookies[$cookie['domain']])) {
                $this->cookies[$cookie['domain']] = array();
            }
            if (!isset($this->cookies[$cookie['domain']][$cookie['path']])) {
                $this->cookies[$cookie['domain']][$cookie['path']] = array();
            }
            $this->cookies[$cookie['domain']][$cookie['path']][$cookie['name']] = $cookie;

        } elseif (isset($this->cookies[$cookie['domain']][$cookie['path']][$cookie['name']])) {
            unset($this->cookies[$cookie['domain']][$cookie['path']][$cookie['name']]);
        }

        return true;
    }
    public function addCookiesFromResponse(HTTP_Request2_Response $response, Net_URL2 $setter = null)
    {
        if (null === $setter) {
            if (!($effectiveUrl = $response->getEffectiveUrl())) {
                throw new HTTP_Request2_LogicException(
                    'Response URL required for adding cookies from response',
                    HTTP_Request2_Exception::MISSING_VALUE
                );
            }
            $setter = new Net_URL2($effectiveUrl);
        }

        $success = true;
        foreach ($response->getCookies() as $cookie) {
            $success = $this->store($cookie, $setter) && $success;
        }
        return $success;
    }
    public function getMatching(Net_URL2 $url, $asString = false)
    {
        $host   = $url->getHost();
        $path   = $url->getPath();
        $secure = 0 == strcasecmp($url->getScheme(), 'https');

        $matched = $ret = array();
        foreach (array_keys($this->cookies) as $domain) {
            if ($this->domainMatch($host, $domain)) {
                foreach (array_keys($this->cookies[$domain]) as $cPath) {
                    if (0 === strpos($path, $cPath)) {
                        foreach ($this->cookies[$domain][$cPath] as $name => $cookie) {
                            if (!$cookie['secure'] || $secure) {
                                $matched[$name][strlen($cookie['path'])] = $cookie;
                            }
                        }
                    }
                }
            }
        }
        foreach ($matched as $cookies) {
            krsort($cookies);
            $ret = array_merge($ret, $cookies);
        }
        if (!$asString) {
            return $ret;
        } else {
            $str = '';
            foreach ($ret as $c) {
                $str .= (empty($str)? '': '; ') . $c['name'] . '=' . $c['value'];
            }
            return $str;
        }
    }
    public function getAll()
    {
        $cookies = array();
        foreach (array_keys($this->cookies) as $domain) {
            foreach (array_keys($this->cookies[$domain]) as $path) {
                foreach ($this->cookies[$domain][$path] as $name => $cookie) {
                    $cookies[] = $cookie;
                }
            }
        }
        return $cookies;
    }
    public function serializeSessionCookies($serialize)
    {
        $this->serializeSession = (bool)$serialize;
    }
    public function ignoreInvalidCookies($ignore)
    {
        $this->ignoreInvalid = (bool)$ignore;
    }
    public function usePublicSuffixList($useList)
    {
        $this->useList = (bool)$useList;
    }
    public function serialize()
    {
        $cookies = $this->getAll();
        if (!$this->serializeSession) {
            for ($i = count($cookies) - 1; $i >= 0; $i--) {
                if (empty($cookies[$i]['expires'])) {
                    unset($cookies[$i]);
                }
            }
        }
        return serialize(array(
            'cookies'          => $cookies,
            'serializeSession' => $this->serializeSession,
            'useList'          => $this->useList,
            'ignoreInvalid'    => $this->ignoreInvalid
        ));
    }
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $now  = $this->now();
        $this->serializeSessionCookies($data['serializeSession']);
        $this->usePublicSuffixList($data['useList']);
        if (array_key_exists('ignoreInvalid', $data)) {
            $this->ignoreInvalidCookies($data['ignoreInvalid']);
        }
        foreach ($data['cookies'] as $cookie) {
            if (!empty($cookie['expires']) && $cookie['expires'] <= $now) {
                continue;
            }
            if (!isset($this->cookies[$cookie['domain']])) {
                $this->cookies[$cookie['domain']] = array();
            }
            if (!isset($this->cookies[$cookie['domain']][$cookie['path']])) {
                $this->cookies[$cookie['domain']][$cookie['path']] = array();
            }
            $this->cookies[$cookie['domain']][$cookie['path']][$cookie['name']] = $cookie;
        }
    }
    public function domainMatch($requestHost, $cookieDomain)
    {
        if ($requestHost == $cookieDomain) {
            return true;
        }
        if (preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $requestHost)) {
            return false;
        }
        if ('.' != $cookieDomain[0]) {
            $cookieDomain = '.' . $cookieDomain;
        }
        if (!$this->useList && substr_count($cookieDomain, '.') < 2
            || $this->useList && !self::getRegisteredDomain($cookieDomain)
        ) {
            return false;
        }
        return substr('.' . $requestHost, -strlen($cookieDomain)) == $cookieDomain;
    }
    public static function getRegisteredDomain($domain)
    {
        $domainParts = explode('.', ltrim($domain, '.'));
        if (empty(self::$psl)) {
            $path = '@data_dir@' . DIRECTORY_SEPARATOR . 'HTTP_Request2';
            if (0 === strpos($path, '@' . 'data_dir@')) {
                $path = realpath(
                    dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data'
                );
            }
            self::$psl = include_once $path . DIRECTORY_SEPARATOR . 'public-suffix-list.php';
        }

        if (!($result = self::checkDomainsList($domainParts, self::$psl))) {
            return false;
        }
        if (!strpos($result, '.')) {
            if (2 > ($count = count($domainParts))) {
                return false;
            }
            return $domainParts[$count - 2] . '.' . $domainParts[$count - 1];
        }
        return $result;
    }
    protected static function checkDomainsList(array $domainParts, $listNode)
    {
        $sub    = array_pop($domainParts);
        $result = null;

        if (!is_array($listNode) || is_null($sub)
            || array_key_exists('!' . $sub, $listNode)
        ) {
            return $sub;

        } elseif (array_key_exists($sub, $listNode)) {
            $result = self::checkDomainsList($domainParts, $listNode[$sub]);

        } elseif (array_key_exists('*', $listNode)) {
            $result = self::checkDomainsList($domainParts, $listNode['*']);

        } else {
            return $sub;
        }

        return (strlen($result) > 0) ? ($result . '.' . $sub) : null;
    }
}
?>
