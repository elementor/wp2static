<?php
/**
 * HTTP_Request2_SOCKS5
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2/SocketWrapper.php';
class HTTP_Request2_SOCKS5 extends HTTP_Request2_SocketWrapper
{
    public function __construct(
        $address, $timeout = 10, array $contextOptions = array(),
        $username = null, $password = null
    ) {
        parent::__construct($address, $timeout, $contextOptions);

        if (strlen($username)) {
            $request = pack('C4', 5, 2, 0, 2);
        } else {
            $request = pack('C3', 5, 1, 0);
        }
        $this->write($request);
        $response = unpack('Cversion/Cmethod', $this->read(3));
        if (5 != $response['version']) {
            throw new HTTP_Request2_MessageException(
                'Invalid version received from SOCKS5 proxy: ' . $response['version'],
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        }
        switch ($response['method']) {
        case 2:
            $this->performAuthentication($username, $password);
        case 0:
            break;
        default:
            throw new HTTP_Request2_ConnectionException(
                "Connection rejected by proxy due to unsupported auth method"
            );
        }
    }
    protected function performAuthentication($username, $password)
    {
        $request  = pack('C2', 1, strlen($username)) . $username
                    . pack('C', strlen($password)) . $password;

        $this->write($request);
        $response = unpack('Cvn/Cstatus', $this->read(3));
        if (1 != $response['vn'] || 0 != $response['status']) {
            throw new HTTP_Request2_ConnectionException(
                'Connection rejected by proxy due to invalid username and/or password'
            );
        }
    }
    public function connect($remoteHost, $remotePort)
    {
        $request = pack('C5', 0x05, 0x01, 0x00, 0x03, strlen($remoteHost))
                   . $remoteHost . pack('n', $remotePort);

        $this->write($request);
        $response = unpack('Cversion/Creply/Creserved', $this->read(1024));
        if (5 != $response['version'] || 0 != $response['reserved']) {
            throw new HTTP_Request2_MessageException(
                'Invalid response received from SOCKS5 proxy',
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        } elseif (0 != $response['reply']) {
            throw new HTTP_Request2_ConnectionException(
                "Unable to connect to {$remoteHost}:{$remotePort} through SOCKS5 proxy",
                0, $response['reply']
            );
        }
    }
}
?>
