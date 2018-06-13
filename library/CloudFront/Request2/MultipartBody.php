<?php
/**
 * Helper class for building multipart/form-data request body
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

/** Exception class for HTTP_Request2 package */
require_once 'HTTP/Request2/Exception.php';

/**
 * Class for building multipart/form-data request body
 *
 * The class helps to reduce memory consumption by streaming large file uploads
 * from disk, it also allows monitoring of upload progress (see request #7630)
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://tools.ietf.org/html/rfc1867
 */
class HTTP_Request2_MultipartBody
{
    /**
     * MIME boundary
     * @var  string
     */
    private $_boundary;

    /**
     * Form parameters added via {@link HTTP_Request2::addPostParameter()}
     * @var  array
     */
    private $_params = array();

    /**
     * File uploads added via {@link HTTP_Request2::addUpload()}
     * @var  array
     */
    private $_uploads = array();

    /**
     * Header for parts with parameters
     * @var  string
     */
    private $_headerParam = "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n";

    /**
     * Header for parts with uploads
     * @var  string
     */
    private $_headerUpload = "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n";

    /**
     * Current position in parameter and upload arrays
     *
     * First number is index of "current" part, second number is position within
     * "current" part
     *
     * @var  array
     */
    private $_pos = array(0, 0);


    /**
     * Constructor. Sets the arrays with POST data.
     *
     * @param array $params      values of form fields set via
     *                           {@link HTTP_Request2::addPostParameter()}
     * @param array $uploads     file uploads set via
     *                           {@link HTTP_Request2::addUpload()}
     * @param bool  $useBrackets whether to append brackets to array variable names
     */
    public function __construct(array $params, array $uploads, $useBrackets = true)
    {
        $this->_params = self::_flattenArray('', $params, $useBrackets);
        foreach ($uploads as $fieldName => $f) {
            if (!is_array($f['fp'])) {
                $this->_uploads[] = $f + array('name' => $fieldName);
            } else {
                for ($i = 0; $i < count($f['fp']); $i++) {
                    $upload = array(
                        'name' => ($useBrackets? $fieldName . '[' . $i . ']': $fieldName)
                    );
                    foreach (array('fp', 'filename', 'size', 'type') as $key) {
                        $upload[$key] = $f[$key][$i];
                    }
                    $this->_uploads[] = $upload;
                }
            }
        }
    }

    /**
     * Returns the length of the body to use in Content-Length header
     *
     * @return   integer
     */
    public function getLength()
    {
        $boundaryLength     = strlen($this->getBoundary());
        $headerParamLength  = strlen($this->_headerParam) - 4 + $boundaryLength;
        $headerUploadLength = strlen($this->_headerUpload) - 8 + $boundaryLength;
        $length             = $boundaryLength + 6;
        foreach ($this->_params as $p) {
            $length += $headerParamLength + strlen($p[0]) + strlen($p[1]) + 2;
        }
        foreach ($this->_uploads as $u) {
            $length += $headerUploadLength + strlen($u['name']) + strlen($u['type']) +
                       strlen($u['filename']) + $u['size'] + 2;
        }
        return $length;
    }

    /**
     * Returns the boundary to use in Content-Type header
     *
     * @return   string
     */
    public function getBoundary()
    {
        if (empty($this->_boundary)) {
            $this->_boundary = '--' . md5('PEAR-HTTP_Request2-' . microtime());
        }
        return $this->_boundary;
    }

    /**
     * Returns next chunk of request body
     *
     * @param integer $length Number of bytes to read
     *
     * @return   string  Up to $length bytes of data, empty string if at end
     * @throws   HTTP_Request2_LogicException
     */
    public function read($length)
    {
        $ret         = '';
        $boundary    = $this->getBoundary();
        $paramCount  = count($this->_params);
        $uploadCount = count($this->_uploads);
        while ($length > 0 && $this->_pos[0] <= $paramCount + $uploadCount) {
            $oldLength = $length;
            if ($this->_pos[0] < $paramCount) {
                $param = sprintf(
                    $this->_headerParam, $boundary, $this->_params[$this->_pos[0]][0]
                ) . $this->_params[$this->_pos[0]][1] . "\r\n";
                $ret    .= substr($param, $this->_pos[1], $length);
                $length -= min(strlen($param) - $this->_pos[1], $length);

            } elseif ($this->_pos[0] < $paramCount + $uploadCount) {
                $pos    = $this->_pos[0] - $paramCount;
                $header = sprintf(
                    $this->_headerUpload, $boundary, $this->_uploads[$pos]['name'],
                    $this->_uploads[$pos]['filename'], $this->_uploads[$pos]['type']
                );
                if ($this->_pos[1] < strlen($header)) {
                    $ret    .= substr($header, $this->_pos[1], $length);
                    $length -= min(strlen($header) - $this->_pos[1], $length);
                }
                $filePos  = max(0, $this->_pos[1] - strlen($header));
                if ($filePos < $this->_uploads[$pos]['size']) {
                    while ($length > 0 && !feof($this->_uploads[$pos]['fp'])) {
                        if (false === ($chunk = fread($this->_uploads[$pos]['fp'], $length))) {
                            throw new HTTP_Request2_LogicException(
                                'Failed reading file upload', HTTP_Request2_Exception::READ_ERROR
                            );
                        }
                        $ret    .= $chunk;
                        $length -= strlen($chunk);
                    }
                }
                if ($length > 0) {
                    $start   = $this->_pos[1] + ($oldLength - $length) -
                               strlen($header) - $this->_uploads[$pos]['size'];
                    $ret    .= substr("\r\n", $start, $length);
                    $length -= min(2 - $start, $length);
                }

            } else {
                $closing  = '--' . $boundary . "--\r\n";
                $ret     .= substr($closing, $this->_pos[1], $length);
                $length  -= min(strlen($closing) - $this->_pos[1], $length);
            }
            if ($length > 0) {
                $this->_pos     = array($this->_pos[0] + 1, 0);
            } else {
                $this->_pos[1] += $oldLength;
            }
        }
        return $ret;
    }

    /**
     * Sets the current position to the start of the body
     *
     * This allows reusing the same body in another request
     */
    public function rewind()
    {
        $this->_pos = array(0, 0);
        foreach ($this->_uploads as $u) {
            rewind($u['fp']);
        }
    }

    /**
     * Returns the body as string
     *
     * Note that it reads all file uploads into memory so it is a good idea not
     * to use this method with large file uploads and rely on read() instead.
     *
     * @return   string
     */
    public function __toString()
    {
        $this->rewind();
        return $this->read($this->getLength());
    }


    /**
     * Helper function to change the (probably multidimensional) associative array
     * into the simple one.
     *
     * @param string $name        name for item
     * @param mixed  $values      item's values
     * @param bool   $useBrackets whether to append [] to array variables' names
     *
     * @return   array   array with the following items: array('item name', 'item value');
     */
    private static function _flattenArray($name, $values, $useBrackets)
    {
        if (!is_array($values)) {
            return array(array($name, $values));
        } else {
            $ret = array();
            foreach ($values as $k => $v) {
                if (empty($name)) {
                    $newName = $k;
                } elseif ($useBrackets) {
                    $newName = $name . '[' . $k . ']';
                } else {
                    $newName = $name;
                }
                $ret = array_merge($ret, self::_flattenArray($newName, $v, $useBrackets));
            }
            return $ret;
        }
    }
}
?>
