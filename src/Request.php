<?php

namespace WP2Static;

class Request {

    /**
     * @var string | bool
     */
    public $body;
    /**
     * @var mixed[]
     */
    public $default_options;
    /**
     * @var mixed[]
     */
    public $headers;
    /**
     * @var int
     */
    public $status_code;

    public function __construct() {
        $this->default_options = [
            // TODO: allow overriding all cURL options
            // CURLOPT_USERAGENT => 'WP2Static.com',
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
    }

    /**
     * Apply default cURL options
     *
     * @param resource $ch cURL resource
     */
    public function applyDefaultOptions( $ch ) : void {
        foreach ( $this->default_options as $option => $value ) {
            curl_setopt(
                $ch,
                $option,
                $value
            );
        }
    }

    /**
     * GET with cURL handle and options
     *
     * @param resource $ch cURL resource
     * @return mixed[] response and cURL handle in array
     */
    public function getURL(
        string $url,
        $ch
    ) : ?array {
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HEADER, 1 );

        // $this->applyDefaultOptions( $ch );

        $response = curl_exec( $ch );
        if ( true === $response ) {
            WsLog::l( 'Error: CURLOPT_RETURNTRANSFER not set.' );
            return null;
        } elseif ( false === $response ) {
            WsLog::l( "Error while retrieving URL: $url" );
            return null;
        }

        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
        $header_text = substr( $response, 0, $header_size );
        $headers = array_filter( explode( "\r\n", $header_text ) );

        $response = [
            'body' => substr( $response, $header_size ),
            'ch' => $ch,
            'code' => curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ),
            'effective_url' => curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL ),
            'headers' => $headers,
        ];

        return $response;
    }

    /**
     * POST with JSON payload and custom headers
     *
     * @param mixed[] $data payload
     * @param mixed[] $headers custom headers
     */
    public function postWithJSONPayloadCustomHeaders(
        string $url,
        array $data,
        array $headers
        ) : void {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );

        $this->applyDefaultOptions( $ch );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode( $data )
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );
    }

    /**
     * GET with custom headers
     *
     * @param mixed[] $headers custom headers
     */
    public function getWithCustomHeaders(
        string $url,
        array $headers
    ) : int {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $this->applyDefaultOptions( $ch );

        $output = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );

        if ( ! is_string( $output ) ) {
            return 0;
        }

        $this->body = substr( $output, $header_size );
        $header = substr( $output, 0, $header_size );

        $raw_headers = explode(
            "\n",
            trim( mb_substr( $output, 0, $header_size ) )
        );

        unset( $raw_headers[0] );

        $this->headers = [];

        foreach ( $raw_headers as $line ) {
            list( $key, $val ) = explode( ':', $line, 2 );
            $this->headers[ strtolower( $key ) ] = trim( $val );
        }

        curl_close( $ch );

        return $this->status_code;
    }

    /**
     * PUT with JSON payload and custom headers
     *
     * @param mixed[] $data payload
     * @param mixed[] $headers custom headers
     */
    public function putWithJSONPayloadCustomHeaders(
        string $url,
        array $data,
        array $headers
        ) : void {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode( $data )
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );
    }

    /**
     * PUT file with custom headers
     *
     * @param mixed[] $headers custom headers
     */
    public function putWithFileStreamAndHeaders(
        string $url,
        string $local_file,
        array $headers
        ) : void {

        $ch = curl_init();

        $file_stream = fopen( $local_file, 'r' );

        $data_length = filesize( $local_file );

        if ( ! $data_length ) {
            return;
        }

        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_UPLOAD, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_INFILE, $file_stream );
        curl_setopt( $ch, CURLOPT_INFILESIZE, $data_length );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $this->applyDefaultOptions( $ch );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        if ( curl_errno( $ch ) ) {
            WsLog::l( 'cURL error: ' . curl_error( $ch ) );
        }

        curl_close( $ch );

        if ( is_resource( $file_stream ) ) {
            fclose( $file_stream );
        }
    }

    /**
     *  POST with file handle and custom headers
     *
     *  @param mixed[] $headers header options
     */
    public function postWithFileStreamAndHeaders(
        string $url,
        string $local_file,
        array $headers
        ) : void {
        $ch = curl_init();

        $file_stream = fopen( $local_file, 'r' );
        $data_length = filesize( $local_file );

        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_UPLOAD, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_INFILE, $file_stream );
        curl_setopt( $ch, CURLOPT_INFILESIZE, $data_length );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $this->applyDefaultOptions( $ch );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );

        if ( is_resource( $file_stream ) ) {
            fclose( $file_stream );
        }
    }

    /**
     *  POST with options array
     *
     * @param mixed[] $data payload
     */
    public function postWithArray(
        string $url,
        array $data
        ) : void {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        $this->applyDefaultOptions( $ch );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $data
        );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );
    }

    /***
     * Get Response code with standard headers
     *
     * @param $url string url to get response code
     * @return int response code
     */
    public function getResponseCode( string $url ) : int {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

        $this->applyDefaultOptions( $ch );

        $this->body = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $this->status_code = $status_code;

        curl_close( $ch );

        return $status_code;
    }
}

