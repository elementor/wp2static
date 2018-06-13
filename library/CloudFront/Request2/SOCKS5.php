<?php
/**
 * SOCKS5 proxy connection class
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/** Socket wrapper class used by Socket Adapter */
require_once 'HTTP/Request2/SocketWrapper.php';

/**
 * SOCKS5 proxy connection class (used by Socket Adapter)
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://pear.php.net/bugs/bug.php?id=19332
 * @link     http://tools.ietf.org/html/rfc1928
 */
class HTTP_Request2_SOCKS5 extends HTTP_Request2_SocketWrapper
{
    /**
     * Constructor, tries to connect and authenticate to a SOCKS5 proxy
     *
     * @param string $address        Proxy address, e.g. 'tcp://localhost:1080'
     * @param int    $timeout        Connection timeout (seconds)
     * @param array  $contextOptions Stream context options
     * @param string $username       Proxy user name
     * @param string $password       Proxy password
     *
     * @throws HTTP_Request2_LogicException
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     */
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

    /**
     * Performs username/password authentication for SOCKS5
     *
     * @param string $username Proxy user name
     * @param string $password Proxy password
     *
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     * @link http://tools.ietf.org/html/rfc1929
     */
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

    /**
     * Connects to a remote host via proxy
     *
     * @param string $remoteHost Remote host
     * @param int    $remotePort Remote port
     *
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     */
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