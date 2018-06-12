<?php
namespace FtpClient;
class FtpWrapper
{
    protected $conn;
    public function __construct(&$connection)
    {
        $this->conn = &$connection;
    }
    public function __call($function, array $arguments)
    {
        $function = 'ftp_' . $function;

        if (function_exists($function)) {
            array_unshift($arguments, $this->conn);
            return call_user_func_array($function, $arguments);
        }

        throw new FtpException("{$function} is not a valid FTP function");
    }
    public function connect($host, $port = 21, $timeout = 90)
    {
        return ftp_connect($host, $port, $timeout);
    }
    public function ssl_connect($host, $port = 21, $timeout = 90)
    {
        return ftp_ssl_connect($host, $port, $timeout);
    }
}
