<?php
namespace Aws\Credentials;
use Aws;
use Aws\CacheInterface;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Promise;
class CredentialProvider
{
    const ENV_KEY = 'AWS_ACCESS_KEY_ID';
    const ENV_SECRET = 'AWS_SECRET_ACCESS_KEY';
    const ENV_SESSION = 'AWS_SESSION_TOKEN';
    const ENV_PROFILE = 'AWS_PROFILE';
    public static function defaultProvider(array $config = [])
    {
        $localCredentialProviders = self::localCredentialProviders();
        $remoteCredentialProviders = self::remoteCredentialProviders($config);
        return self::memoize(
            call_user_func_array(
                'self::chain',
                array_merge($localCredentialProviders, $remoteCredentialProviders)
            )
        );
    }
    public static function fromCredentials(CredentialsInterface $creds)
    {
        $promise = Promise\promise_for($creds);
        return function () use ($promise) {
            return $promise;
        };
    }
    public static function chain()
    {
        $links = func_get_args();
        if (empty($links)) {
            throw new \InvalidArgumentException('No providers in chain');
        }
        return function () use ($links) {
            $parent = array_shift($links);
            $promise = $parent();
            while ($next = array_shift($links)) {
                $promise = $promise->otherwise($next);
            }
            return $promise;
        };
    }
    public static function memoize(callable $provider)
    {
        return function () use ($provider) {
            static $result;
            static $isConstant;
            if ($isConstant) {
                return $result;
            }
            if (null === $result) {
                $result = $provider();
            }
            return $result
                ->then(function (CredentialsInterface $creds) use ($provider, &$isConstant, &$result) {
                    if (!$creds->getExpiration()) {
                        $isConstant = true;
                        return $creds;
                    }
                    if (!$creds->isExpired()) {
                        return $creds;
                    }
                    return $result = $provider();
                });
        };
    }
    public static function cache(
        callable $provider,
        CacheInterface $cache,
        $cacheKey = null
    ) {
        $cacheKey = $cacheKey ?: 'aws_cached_credentials';
        return function () use ($provider, $cache, $cacheKey) {
            $found = $cache->get($cacheKey);
            if ($found instanceof CredentialsInterface && !$found->isExpired()) {
                return Promise\promise_for($found);
            }
            return $provider()
                ->then(function (CredentialsInterface $creds) use (
                    $cache,
                    $cacheKey
                ) {
                    $cache->set(
                        $cacheKey,
                        $creds,
                        null === $creds->getExpiration() ?
                            0 : $creds->getExpiration() - time()
                    );
                    return $creds;
                });
        };
    }
    public static function env()
    {
        return function () {
            $key = getenv(self::ENV_KEY);
            $secret = getenv(self::ENV_SECRET);
            if ($key && $secret) {
                return Promise\promise_for(
                    new Credentials($key, $secret, getenv(self::ENV_SESSION) ?: NULL)
                );
            }
            return self::reject('Could not find environment variable '
                . 'credentials in ' . self::ENV_KEY . '/' . self::ENV_SECRET);
        };
    }
    public static function instanceProfile(array $config = [])
    {
        return new InstanceProfileProvider($config);
    }
    public static function ecsCredentials(array $config = [])
    {
        return new EcsCredentialProvider($config);
    }
    public static function assumeRole(array $config=[])
    {
        return new AssumeRoleCredentialProvider($config);
    }
    public static function ini($profile = null, $filename = null)
    {
        $filename = $filename ?: (self::getHomeDir() . '/.aws/credentials');
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');
        return function () use ($profile, $filename) {
            if (!is_readable($filename)) {
                return self::reject("Cannot read credentials from $filename");
            }
            $data = parse_ini_file($filename, true);
            if ($data === false) {
                return self::reject("Invalid credentials file: $filename");
            }
            if (!isset($data[$profile])) {
                return self::reject("'$profile' not found in credentials file");
            }
            if (!isset($data[$profile]['aws_access_key_id'])
                || !isset($data[$profile]['aws_secret_access_key'])
            ) {
                return self::reject("No credentials present in INI profile "
                    . "'$profile' ($filename)");
            }
            if (empty($data[$profile]['aws_session_token'])) {
                $data[$profile]['aws_session_token']
                    = isset($data[$profile]['aws_security_token'])
                        ? $data[$profile]['aws_security_token']
                        : null;
            }
            return Promise\promise_for(
                new Credentials(
                    $data[$profile]['aws_access_key_id'],
                    $data[$profile]['aws_secret_access_key'],
                    $data[$profile]['aws_session_token']
                )
            );
        };
    }
    private static function localCredentialProviders()
    {
        return [
            self::env(),
            self::ini(),
            self::ini('profile default', self::getHomeDir() . '/.aws/config')
        ];
    }
    private static function remoteCredentialProviders(array $config = [])
    {
        if (!empty(getenv(EcsCredentialProvider::ENV_URI))) {
            $providers['ecs'] = self::ecsCredentials($config);
        }
        $providers['instance'] = self::instanceProfile($config);
        if (isset($config['credentials'])
            && $config['credentials'] instanceof CacheInterface
        ) {
            foreach ($providers as $key => $provider) {
                $providers[$key] = self::cache(
                    $provider,
                    $config['credentials'],
                    'aws_cached_' . $key . '_credentials'
                );
            }
        }
        return $providers;
    }
    private static function getHomeDir()
    {
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');
        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }
    private static function reject($msg)
    {
        return new Promise\RejectedPromise(new CredentialsException($msg));
    }
}
