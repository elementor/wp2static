<?php
namespace Monolog\Handler;
use Monolog\Logger;
class LogEntriesHandler extends SocketHandler
{
    protected $logToken;
    public function __construct($token, $useSSL = true, $level = Logger::DEBUG, $bubble = true)
    {
        if ($useSSL && !extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP plugin is required to use SSL encrypted connection for LogEntriesHandler');
        }
        $endpoint = $useSSL ? 'ssl:
        parent::__construct($endpoint, $level, $bubble);
        $this->logToken = $token;
    }
    protected function generateDataStream($record)
    {
        return $this->logToken . ' ' . $record['formatted'];
    }
}
