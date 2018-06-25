<?php
namespace Monolog\Handler;
use Monolog\Logger;
use Monolog\Handler\SyslogUdp\UdpSocket;
class SyslogUdpHandler extends AbstractSyslogHandler
{
    protected $socket;
    public function __construct($host, $port = 514, $facility = LOG_USER, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($facility, $level, $bubble);
        $this->socket = new UdpSocket($host, $port ?: 514);
    }
    protected function write(array $record)
    {
        $lines = $this->splitMessageIntoLines($record['formatted']);
        $header = $this->makeCommonSyslogHeader($this->logLevels[$record['level']]);
        foreach ($lines as $line) {
            $this->socket->write($line, $header);
        }
    }
    public function close()
    {
        $this->socket->close();
    }
    private function splitMessageIntoLines($message)
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        return preg_split('/$\R?^/m', $message);
    }
    protected function makeCommonSyslogHeader($severity)
    {
        $priority = $severity + $this->facility;
        return "<$priority>1 ";
    }
    public function setSocket($socket)
    {
        $this->socket = $socket;
    }
}
