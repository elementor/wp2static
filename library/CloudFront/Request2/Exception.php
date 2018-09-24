<?php
/**
 * HTTP_Request2_Exception
 *
 * @package WP2Static
 */

require_once 'PEAR/Exception.php';
class HTTP_Request2_Exception extends PEAR_Exception
{
    const INVALID_ARGUMENT   = 1;
    const MISSING_VALUE      = 2;
    const MISCONFIGURATION   = 3;
    const READ_ERROR         = 4;
    const MALFORMED_RESPONSE = 10;
    const DECODE_ERROR       = 20;
    const TIMEOUT            = 30;
    const TOO_MANY_REDIRECTS = 40;
    const NON_HTTP_REDIRECT  = 50;
    private $_nativeCode;
    public function __construct($message = null, $code = null, $nativeCode = null)
    {
        parent::__construct($message, $code);
        $this->_nativeCode = $nativeCode;
    }
    public function getNativeCode()
    {
        return $this->_nativeCode;
    }
}
class HTTP_Request2_NotImplementedException extends HTTP_Request2_Exception
{
}
class HTTP_Request2_LogicException extends HTTP_Request2_Exception
{
}
class HTTP_Request2_ConnectionException extends HTTP_Request2_Exception
{
}
class HTTP_Request2_MessageException extends HTTP_Request2_Exception
{
}
?>
