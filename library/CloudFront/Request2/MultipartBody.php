<?php
/**
 * HTTP_Request2_MultipartBody
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2/Exception.php';
class HTTP_Request2_MultipartBody
{
    private $_boundary;
    private $_params = array();
    private $_uploads = array();
    private $_headerParam = "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n";
    private $_headerUpload = "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n";
    private $_pos = array(0, 0);
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
    public function getBoundary()
    {
        if (empty($this->_boundary)) {
            $this->_boundary = '--' . md5('PEAR-HTTP_Request2-' . microtime());
        }
        return $this->_boundary;
    }
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
    public function rewind()
    {
        $this->_pos = array(0, 0);
        foreach ($this->_uploads as $u) {
            rewind($u['fp']);
        }
    }
    public function __toString()
    {
        $this->rewind();
        return $this->read($this->getLength());
    }
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
