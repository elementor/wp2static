<?php
/**
 * S3
 *
 * @package WP2Static
 */

class S3 {

    const DEFAULT_ENDPOINT = 's3.amazonaws.com';

    private $access_key;
    private $secret_key;
    private $endpoint;
    private $multi_curl;
    private $curl_opts;

    public function __construct($access_key, $secret_key, $endpoint = null) {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->endpoint = $endpoint ?: self::DEFAULT_ENDPOINT;

        $this->multi_curl = curl_multi_init();

        $this->curl_opts = array(
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME => 30
        );
    }

    public function __destruct() {
        curl_multi_close($this->multi_curl);
    }

    public function useCurlOpts($curl_opts) {
        $this->curl_opts = $curl_opts;
        return $this;
    }

    public function putObject($bucket, $path, $file, $headers = array()) {
        $uri = "$bucket/$path";

        $request = (new S3Request('PUT', $this->endpoint, $uri))
            ->setFileContents($file)
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }


}

class S3Request {

    private $action;
    private $endpoint;
    private $uri;
    private $headers;
    private $curl;
    private $response;
    private $multi_curl;

    public function __construct($action, $endpoint, $uri) {
        $this->action = $action;
        $this->endpoint = $endpoint;
        $this->uri = $uri;

        $this->headers = array(
            'Content-MD5' => '',
            'Content-Type' => '',
            'Date' => gmdate('D, d M Y H:i:s T'),
            'Host' => $this->endpoint
        );

        $this->curl = curl_init();
        $this->response = new S3Response();

        $this->multi_curl = null;
    }

    public function saveToResource($resource) {
        $this->response->saveToResource($resource);
    }

    public function setFileContents($file) {
        if (is_resource($file)) {
            $hash_ctx = hash_init('md5');
            $length = hash_update_stream($hash_ctx, $file);
            $md5 = hash_final($hash_ctx, true);

            rewind($file);

            curl_setopt($this->curl, CURLOPT_PUT, true);
            curl_setopt($this->curl, CURLOPT_INFILE, $file);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $length);
        } else {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $file);
            $md5 = md5($file, true);
        }

        $this->headers['Content-MD5'] = base64_encode($md5);

        return $this;
    }

    public function setHeaders($custom_headers) {
        $this->headers = array_merge($this->headers, $custom_headers);
        return $this;
    }

    public function sign($access_key, $secret_key) {
        $canonical_amz_headers = $this->getCanonicalAmzHeaders();

        $string_to_sign = '';
        $string_to_sign .= "{$this->action}\n";
        $string_to_sign .= "{$this->headers['Content-MD5']}\n";
        $string_to_sign .= "{$this->headers['Content-Type']}\n";
        $string_to_sign .= "{$this->headers['Date']}\n";

        if (!empty($canonical_amz_headers)) {
            $string_to_sign .= implode($canonical_amz_headers, "\n") . "\n";
        }

        $string_to_sign .= "/{$this->uri}";

        $signature = base64_encode(
            hash_hmac('sha1', $string_to_sign, $secret_key, true)
        );

        $this->headers['Authorization'] = "AWS $access_key:$signature";

        return $this;
    }

    public function useMultiCurl($mh) {
        $this->multi_curl = $mh;
        return $this;
    }

    public function useCurlOpts($curl_opts) {
        curl_setopt_array($this->curl, $curl_opts);

        return $this;
    }

    public function getResponse() {
        $http_headers = array_map(
            function($header, $value) {
                return "$header: $value";
            },
            array_keys($this->headers),
            array_values($this->headers)
        );

        curl_setopt_array($this->curl, array(
            CURLOPT_USERAGENT => 'leonstafford/wordpress-static-html-plugin',
            CURLOPT_URL => "https://{$this->endpoint}/{$this->uri}",
            CURLOPT_HTTPHEADER => $http_headers,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_VERBOSE => false,
			CURLOPT_STDERR, fopen('php://stderr', 'w'),
            CURLOPT_WRITEFUNCTION => array(
                $this->response, '__curlWriteFunction'
            ),
            CURLOPT_HEADERFUNCTION => array(
                $this->response, '__curlHeaderFunction'
            )
        ));

        switch ($this->action) {
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
        }

        if (isset($this->multi_curl)) {
            curl_multi_add_handle($this->multi_curl, $this->curl);

            $running = null;
            do {
                curl_multi_exec($this->multi_curl, $running);
                curl_multi_select($this->multi_curl);
            } while ($running > 0);

            curl_multi_remove_handle($this->multi_curl, $this->curl);
        } else {
            $success = curl_exec($this->curl);
        }

        $this->response->finalize($this->curl);

        curl_close($this->curl);

        return $this->response;
    }

    private function getCanonicalAmzHeaders() {
        $canonical_amz_headers = array();

        foreach ($this->headers as $header => $value) {
            $header = trim(strtolower($header));
            $value = trim($value);

            if (strpos($header, 'x-amz-') === 0) {
                $canonical_amz_headers[$header] = "$header:$value";
            }
        }

        ksort($canonical_amz_headers);

        return $canonical_amz_headers;
    }

}

class S3Response {

    public $error;
    public $code;
    public $headers;
    public $body;

    public function __construct() {
        $this->error = null;
        $this->code = null;
        $this->headers = array();
        $this->body = null;
    }

    public function saveToResource($resource) {
        $this->body = $resource;
    }

    public function __curlWriteFunction($ch, $data) {
        if (is_resource($this->body)) {
            return fwrite($this->body, $data);
        } else {
            $this->body .= $data;
            return strlen($data);
        }
    }

    public function __curlHeaderFunction($ch, $data) {
        $header = explode(':', $data);

        if (count($header) == 2) {
            list($key, $value) = $header;
            $this->headers[$key] = trim($value);
        }

        return strlen($data);
    }

    public function finalize($ch) {
        if (is_resource($this->body)) {
            rewind($this->body);
        }

        if (curl_errno($ch) || curl_error($ch)) {
            $this->error = array(
                'code' => curl_errno($ch),
                'message' => curl_error($ch),
            );
        } else {
            $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            if ($this->code > 300 && $content_type == 'application/xml') {
                if (is_resource($this->body)) {
                    $response = simplexml_load_string(
                        stream_get_contents($this->body)
                    );

                    rewind($this->body);
                } else {
                    $response = simplexml_load_string($this->body);
                }

                if ($response) {
                    $error = array(
                        'code' => (string)$response->Code,
                        'message' => (string)$response->Message,
                    );

                    if (isset($response->Resource)) {
                        $error['resource'] = (string)$response->Resource;
                    }

                    $this->error = $error;
                }
            }
        }
    }
}
