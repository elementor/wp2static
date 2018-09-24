<?php
/**
 * HTTP_Request2_Observer_UncompressingDownload
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2/Response.php';
class HTTP_Request2_Observer_UncompressingDownload implements SplObserver
{
    private $_stream;
    private $_streamFilter;
    private $_encoding;
    private $_processingHeader = true;
    private $_startPosition = 0;
    private $_maxDownloadSize;
    private $_redirect = false;
    private $_possibleHeader = '';
    public function __construct($stream, $maxDownloadSize = null)
    {
        $this->_stream = $stream;
        if ($maxDownloadSize) {
            $this->_maxDownloadSize = $maxDownloadSize;
            $this->_startPosition   = ftell($this->_stream);
        }
    }
    public function update(SplSubject $request)
    {
        $event   = $request->getLastEvent();
        $encoded = false;
        switch ($event['name']) {
        case 'receivedHeaders':
            $this->_processingHeader = true;
            $this->_redirect = $event['data']->isRedirect();
            $this->_encoding = strtolower($event['data']->getHeader('content-encoding'));
            $this->_possibleHeader = '';
            break;

        case 'receivedEncodedBodyPart':
            if (!$this->_streamFilter
                && ($this->_encoding === 'deflate' || $this->_encoding === 'gzip')
            ) {
                $this->_streamFilter = stream_filter_append(
                    $this->_stream, 'zlib.inflate', STREAM_FILTER_WRITE
                );
            }
            $encoded = true;

        case 'receivedBodyPart':
            if ($this->_redirect) {
                break;
            }

            if (!$encoded || !$this->_processingHeader) {
                $bytes = fwrite($this->_stream, $event['data']);

            } else {
                $offset = 0;
                $this->_possibleHeader .= $event['data'];
                if ('deflate' === $this->_encoding) {
                    if (2 > strlen($this->_possibleHeader)) {
                        break;
                    }
                    $header = unpack('n', substr($this->_possibleHeader, 0, 2));
                    if (0 == $header[1] % 31) {
                        $offset = 2;
                    }

                } elseif ('gzip' === $this->_encoding) {
                    if (10 > strlen($this->_possibleHeader)) {
                        break;
                    }
                    try {
                        $offset = HTTP_Request2_Response::parseGzipHeader($this->_possibleHeader, false);

                    } catch (HTTP_Request2_MessageException $e) {
                        if (false !== strpos($e->getMessage(), 'data too short')) {
                            break;
                        }
                        throw $e;
                    }
                }

                $this->_processingHeader = false;
                $bytes = fwrite($this->_stream, substr($this->_possibleHeader, $offset));
            }

            if (false === $bytes) {
                throw new HTTP_Request2_MessageException('fwrite failed.');
            }

            if ($this->_maxDownloadSize
                && ftell($this->_stream) - $this->_startPosition > $this->_maxDownloadSize
            ) {
                throw new HTTP_Request2_MessageException(sprintf(
                    'Body length limit (%d bytes) reached',
                    $this->_maxDownloadSize
                ));
            }
            break;

        case 'receivedBody':
            if ($this->_streamFilter) {
                stream_filter_remove($this->_streamFilter);
                $this->_streamFilter = null;
            }
            break;
        }
    }
}
