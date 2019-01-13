<?php

class StaticHtmlOutput_Netlify {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'netlify',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        $this->settings['netlifySiteID'];
        $this->settings['netlifyPersonalAccessToken'];
        $this->base_url = 'https://api.netlify.com';

        $this->detectSiteID();

        switch ( $_POST['ajax_action'] ) {
            case 'test_netlify':
                $this->test_netlify();
                break;
            case 'netlify_do_export':
                $this->deploy();
                break;
        }
    }

    public function detectSiteID() {
        $this->site_id = $this->settings['netlifySiteID'];

        if ( strpos( $this->site_id, 'netlify.com' ) !== false ) {
            return;
        } elseif ( strpos( $this->site_id, '.' ) !== false ) {
            return;
        } elseif ( strlen( $this->site_id ) === 37 ) {
            return;
        } else {
            $this->site_id .= '.netlify.com';
        }
    }

    public function deploy() {
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $archive = new Archive();
        $archive->setToCurrentArchive();
        $zip_archive_path = $this->settings['wp_uploads_path'] . '/' .
            $archive->name . '.zip';

        $zip_deploy_endpoint = $this->base_url . '/api/v1/sites/' .
            $this->site_id . '/deploys';

        try {
            $ch = curl_init();

            $file_stream = fopen( $zip_archive_path, 'r' );
            $data_length = filesize( $zip_archive_path );

            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $ch, CURLOPT_URL, $zip_deploy_endpoint );
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
                array(
                    'Authorization: Bearer ' .
                        $this->settings['netlifyPersonalAccessToken'],
                    'Content-Type: application/zip',
                )
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'Netlify API bad response status' );
            }

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'NETLIFY EXPORT ERROR' );
            WsLog::l( $e );
            throw new Exception( $e );
        }
    }

    public function test_netlify() {
        $site_info_endpoint = $this->base_url . '/api/v1/sites/' .
            $this->site_id;

        try {
            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $site_info_endpoint );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Authorization: Bearer ' .
                        $this->settings['netlifyPersonalAccessToken'],
                )
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'Netlify API bad response status' );
            }

            $response_elements =
                json_decode( (string) $output, true );

            if ( isset( $response_elements['updated_at'] ) ) {
                echo 'Last updated at: ' . $response_elements['updated_at'];
            } else {
                echo 'Looks like this is your first time deploying this ' .
                    'site on Netlify - good luck!';
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'NETLIFY TEST ERROR' );
            WsLog::l( $e );
            throw new Exception( $e );
        }
    }
}

$netlify = new StaticHtmlOutput_Netlify();
