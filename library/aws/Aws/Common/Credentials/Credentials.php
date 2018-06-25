<?php
namespace Aws\Common\Credentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\RequiredExtensionNotLoadedException;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\FromConfigInterface;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Common\Collection;
class Credentials implements CredentialsInterface, FromConfigInterface
{
    const ENV_KEY = 'AWS_ACCESS_KEY_ID';
    const ENV_SECRET = 'AWS_SECRET_KEY';
    const ENV_SECRET_ACCESS_KEY = 'AWS_SECRET_ACCESS_KEY';
    const ENV_PROFILE = 'AWS_PROFILE';
    protected $key;
    protected $secret;
    protected $token;
    protected $ttd;
    public static function getConfigDefaults()
    {
        return array(
            Options::KEY                   => null,
            Options::SECRET                => null,
            Options::TOKEN                 => null,
            Options::TOKEN_TTD             => null,
            Options::PROFILE               => null,
            Options::CREDENTIALS_CACHE     => null,
            Options::CREDENTIALS_CACHE_KEY => null,
            Options::CREDENTIALS_CLIENT    => null
        );
    }
    public static function factory($config = array())
    {
        foreach (self::getConfigDefaults() as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        $cache = $config[Options::CREDENTIALS_CACHE];
        $cacheKey = $config[Options::CREDENTIALS_CACHE_KEY] ?:
            'credentials_' . ($config[Options::KEY] ?: crc32(gethostname()));
        if (
            $cacheKey &&
            $cache instanceof CacheAdapterInterface &&
            $cached = self::createFromCache($cache, $cacheKey)
        ) {
            return $cached;
        }
        if (!$config[Options::KEY] || !$config[Options::SECRET]) {
            $credentials = self::createFromEnvironment($config);
        } else {
            $credentials = new static(
                $config[Options::KEY],
                $config[Options::SECRET],
                $config[Options::TOKEN],
                $config[Options::TOKEN_TTD]
            );
        }
        $cache = $config[Options::CREDENTIALS_CACHE];
        if ($cacheKey && $cache) {
            $credentials = self::createCache($credentials, $cache, $cacheKey);
        }
        return $credentials;
    }
    public static function fromIni($profile = null, $filename = null)
    {
        if (!$filename) {
            $filename = self::getHomeDir() . '/.aws/credentials';
        }
        if (!$profile) {
            $profile = self::getEnvVar(self::ENV_PROFILE) ?: 'default';
        }
        if (!is_readable($filename) || ($data = parse_ini_file($filename, true)) === false) {
            throw new \RuntimeException("Invalid AWS credentials file: {$filename}.");
        }
        if (!isset($data[$profile]['aws_access_key_id']) || !isset($data[$profile]['aws_secret_access_key'])) {
            throw new \RuntimeException("Invalid AWS credentials profile {$profile} in {$filename}.");
        }
        return new self(
            $data[$profile]['aws_access_key_id'],
            $data[$profile]['aws_secret_access_key'],
            isset($data[$profile]['aws_security_token'])
                ? $data[$profile]['aws_security_token']
                : null
        );
    }
    public function __construct($accessKeyId, $secretAccessKey, $token = null, $expiration = null)
    {
        $this->key = trim($accessKeyId);
        $this->secret = trim($secretAccessKey);
        $this->token = $token;
        $this->ttd = $expiration;
    }
    public function serialize()
    {
        return json_encode(array(
            Options::KEY       => $this->key,
            Options::SECRET    => $this->secret,
            Options::TOKEN     => $this->token,
            Options::TOKEN_TTD => $this->ttd
        ));
    }
    public function unserialize($serialized)
    {
        $data = json_decode($serialized, true);
        $this->key    = $data[Options::KEY];
        $this->secret = $data[Options::SECRET];
        $this->token  = $data[Options::TOKEN];
        $this->ttd    = $data[Options::TOKEN_TTD];
    }
    public function getAccessKeyId()
    {
        return $this->key;
    }
    public function getSecretKey()
    {
        return $this->secret;
    }
    public function getSecurityToken()
    {
        return $this->token;
    }
    public function getExpiration()
    {
        return $this->ttd;
    }
    public function isExpired()
    {
        return $this->ttd !== null && time() >= $this->ttd;
    }
    public function setAccessKeyId($key)
    {
        $this->key = $key;
        return $this;
    }
    public function setSecretKey($secret)
    {
        $this->secret = $secret;
        return $this;
    }
    public function setSecurityToken($token)
    {
        $this->token = $token;
        return $this;
    }
    public function setExpiration($timestamp)
    {
        $this->ttd = $timestamp;
        return $this;
    }
    private static function createFromEnvironment($config)
    {
        $envKey = self::getEnvVar(self::ENV_KEY);
        if (!($envSecret = self::getEnvVar(self::ENV_SECRET))) {
            $envSecret = self::getEnvVar(self::ENV_SECRET_ACCESS_KEY);
        }
        if ($envKey && $envSecret) {
            return new static($envKey, $envSecret);
        }
        try {
            return self::fromIni($config[Options::PROFILE]);
        } catch (\RuntimeException $e) {
            return new RefreshableInstanceProfileCredentials(
                new static('', '', '', 1),
                $config[Options::CREDENTIALS_CLIENT]
            );
        }
    }
    private static function createFromCache(CacheAdapterInterface $cache, $cacheKey)
    {
        $cached = $cache->fetch($cacheKey);
        if ($cached instanceof CredentialsInterface && !$cached->isExpired()) {
            return new CacheableCredentials($cached, $cache, $cacheKey);
        }
        return null;
    }
    private static function createCache(CredentialsInterface $credentials, $cache, $cacheKey)
    {
        if ($cache === 'true' || $cache === true) {
            if (!extension_loaded('apc')) {
                throw new RequiredExtensionNotLoadedException('PHP has not been compiled with APC. Unable to cache '
                    . 'the credentials.');
            } elseif (!class_exists('Doctrine\Common\Cache\ApcCache')) {
                throw new RuntimeException(
                    'Cannot set ' . Options::CREDENTIALS_CACHE . ' to true because the Doctrine cache component is '
                    . 'not installed. Either install doctrine/cache or pass in an instantiated '
                    . 'Guzzle\Cache\CacheAdapterInterface object'
                );
            }
            $cache = new DoctrineCacheAdapter(new \Doctrine\Common\Cache\ApcCache());
        } elseif (!($cache instanceof CacheAdapterInterface)) {
            throw new InvalidArgumentException('Unable to utilize caching with the specified options');
        }
        return new CacheableCredentials($credentials, $cache, $cacheKey);
    }
    private static function getHomeDir()
    {
        if ($homeDir = self::getEnvVar('HOME')) {
            return $homeDir;
        }
        $homeDrive = self::getEnvVar('HOMEDRIVE');
        $homePath = self::getEnvVar('HOMEPATH');
        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }
    private static function getEnvVar($var)
    {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : getenv($var);
    }
}
