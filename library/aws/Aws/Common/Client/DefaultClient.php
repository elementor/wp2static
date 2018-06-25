<?php
namespace Aws\Common\Client;
use Aws\Common\Enum\ClientOptions as Options;
use Guzzle\Common\Collection;
class DefaultClient extends AbstractClient
{
    public static function factory($config = array())
    {
        return ClientBuilder::factory()
            ->setConfig($config)
            ->setConfigDefaults(array(Options::SCHEME => 'https'))
            ->build();
    }
}
