<?php

class WP2Static_Request {

    public function __construct() {
        $this->default_options = array(
            CURLOPT_USERAGENT => 'WP2Static.com',
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 600,
        );
    }

    public function applyDefaultOptions( $curl_handle ) {
        foreach ( $this->default_options as $option => $value ) {
            curl_setopt(
                $curl_handle,
                $option,
                $value
            );
        }
    }

    public function postWithJSONPayloadCustomHeaders(
        $url,
        $data,
        $headers,
        $curl_options = array()
        ) {
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

        if ( ! empty( $curl_options ) ) {
            foreach ( $curl_options as $option => $value ) {
                curl_setopt(
                    $ch,
                    $option,
                    $value
                );
            }
        }

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

    public function getWithCustomHeaders( $url, $headers ) {
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

        $this->body = substr( $output, $header_size );
        $header = substr( $output, 0, $header_size );

        $raw_headers = explode(
            "\n",
            trim( mb_substr( $output, 0, $header_size ) )
        );

        unset( $raw_headers[0] );

        $this->headers = array();

        foreach ( $raw_headers as $line ) {
            list( $key, $val ) = explode( ':', $line, 2 );
            $this->headers[ strtolower( $key ) ] = trim( $val );
        }

        curl_close( $ch );
    }

    public function putWithJSONPayloadCustomHeaders(
        $url,
        $data,
        $headers
        ) {
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

    public function putWithFileStreamAndHeaders(
        $url,
        $local_file,
        $headers
        ) {
        $ch = curl_init();

        $file_stream = fopen( $local_file, 'r' );
        $data_length = filesize( $local_file );

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

        curl_close( $ch );
    }

    public function postWithFileStreamAndHeaders(
        $url,
        $local_file,
        $headers
        ) {
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
    }

    public function postWithArray(
        $url,
        $data,
        $curl_options = array()
        ) {
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        $this->applyDefaultOptions( $ch );

        if ( ! empty( $curl_options ) ) {
            foreach ( $curl_options as $option => $value ) {
                curl_setopt(
                    $ch,
                    $option,
                    $value
                );
            }
        }

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $data
        );

        $this->body = curl_exec( $ch );
        $this->status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        curl_close( $ch );
    }
}

$wp2static_request = new WP2Static_Request();

