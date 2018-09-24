<?php
/**
 * HTTP_Request2_SocketWrapper
 *
 * @package WP2Static
 */

require_once 'Exception.php';
class HTTP_Request2_SocketWrapper
{
    protected $connectionWarnings = array();
    protected $socket;
    protected $deadline;
    protected $timeout;
    public function __construct($address, $timeout, array $contextOptions = array())
    {
        if (!empty($contextOptions)
            && !isset($contextOptions['socket']) && !isset($contextOptions['ssl'])
        ) {
            $contextOptions = array('ssl' => $contextOptions);
        }
        if (isset($contextOptions['ssl'])) {
            $contextOptions['ssl'] += array(
                // https://wiki.mozilla.org/Security/Server_Side_TLS
                'ciphers' => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:'
                             . 'ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:'
                             . 'DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:'
                             . 'ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:'
                             . 'ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:'
                             . 'ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:'
                             . 'ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:'
                             . 'DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:'
                             . 'DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:'
                             . 'ECDHE-RSA-DES-CBC3-SHA:ECDHE-ECDSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:'
                             . 'AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:'
                             . 'AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:'
                             . '!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA'
            );
            if (version_compare(phpversion(), '5.4.13', '>=')) {
                $contextOptions['ssl']['disable_compression'] = true;
                if (version_compare(phpversion(), '5.6', '>=')) {
                    $contextOptions['ssl']['crypto_method'] = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                                                              | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
            }
        }
        $context = stream_context_create();
        foreach ($contextOptions as $wrapper => $options) {
            foreach ($options as $name => $value) {
                if (!stream_context_set_option($context, $wrapper, $name, $value)) {
                    throw new HTTP_Request2_LogicException(
                        "Error setting '{$wrapper}' wrapper context option '{$name}'"
                    );
                }
            }
        }
        set_error_handler(array($this, 'connectionWarningsHandler'));
        $this->socket = stream_socket_client(
            $address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context
        );
        restore_error_handler();
        // connection still succeeds, albeit with a warning. Throw an Exception
        // to be what user wants and as Curl throws an error in similar case.
        if ($this->connectionWarnings) {
            if ($this->socket) {
                fclose($this->socket);
            }
            $error = $errstr ? $errstr : implode("\n", $this->connectionWarnings);
            throw new HTTP_Request2_ConnectionException(
                "Unable to connect to {$address}. Error: {$error}", 0, $errno
            );
        }
    }
    public function __destruct()
    {
        fclose($this->socket);
    }
    public function read($length)
    {
        if ($this->deadline) {
            stream_set_timeout($this->socket, max($this->deadline - time(), 1));
        }
        $data = fread($this->socket, $length);
        $this->checkTimeout();
        return $data;
    }
    public function readLine($bufferSize, $localTimeout = null)
    {
        $line = '';
        while (!feof($this->socket)) {
            if (null !== $localTimeout) {
                stream_set_timeout($this->socket, $localTimeout);
            } elseif ($this->deadline) {
                stream_set_timeout($this->socket, max($this->deadline - time(), 1));
            }

            $line .= @fgets($this->socket, $bufferSize);

            if (null === $localTimeout) {
                $this->checkTimeout();

            } else {
                $info = stream_get_meta_data($this->socket);
                // prevents further calls failing with a bogus Exception
                if (!$this->deadline) {
                    $default = (int)@ini_get('default_socket_timeout');
                    stream_set_timeout($this->socket, $default > 0 ? $default : PHP_INT_MAX);
                }
                if ($info['timed_out']) {
                    throw new HTTP_Request2_MessageException(
                        "readLine() call timed out", HTTP_Request2_Exception::TIMEOUT
                    );
                }
            }
            if (substr($line, -1) == "\n") {
                return rtrim($line, "\r\n");
            }
        }
        return $line;
    }
    public function write($data)
    {
        if ($this->deadline) {
            stream_set_timeout($this->socket, max($this->deadline - time(), 1));
        }
        $written = fwrite($this->socket, $data);
        $this->checkTimeout();
        if ($written < strlen($data)) {
            throw new HTTP_Request2_MessageException('Error writing request');
        }
        return $written;
    }
    public function eof()
    {
        return feof($this->socket);
    }
    public function setDeadline($deadline, $timeout)
    {
        $this->deadline = $deadline;
        $this->timeout  = $timeout;
    }
    public function enableCrypto()
    {
        if (version_compare(phpversion(), '5.6', '<')) {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        } else {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                            | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }

        if (!stream_socket_enable_crypto($this->socket, true, $cryptoMethod)) {
            throw new HTTP_Request2_ConnectionException(
                'Failed to enable secure connection when connecting through proxy'
            );
        }
    }
    protected function checkTimeout()
    {
        $info = stream_get_meta_data($this->socket);
        if ($info['timed_out'] || $this->deadline && time() > $this->deadline) {
            $reason = $this->deadline
                ? "after {$this->timeout} second(s)"
                : 'due to default_socket_timeout php.ini setting';
            throw new HTTP_Request2_MessageException(
                "Request timed out {$reason}", HTTP_Request2_Exception::TIMEOUT
            );
        }
    }
    protected function connectionWarningsHandler($errno, $errstr)
    {
        if ($errno & E_WARNING) {
            array_unshift($this->connectionWarnings, $errstr);
        }
        return true;
    }
}
?>
