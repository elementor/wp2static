<?php

use GuzzleHttp\Client;

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
        $this->baseURL = 'https://api.netlify.com';

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
        $this->siteID = $this->settings['netlifySiteID'];

        if ( strpos( $site_id, 'netlify.com' ) !== false ) {
            return;
        } elseif ( strpos( $site_id, '.' ) !== false ) {
            return;
        } elseif ( strlen( $site_id ) === 37 ) {
            return;
        } else {
            $this->siteID .= '.netlify.com';
        }
    }

    public function deploy() {
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $archive = new Archive();
        $archive->setToCurrentArchive();
        $zipArchivePath = $this->settings['wp_uploads_path'] . '/' .
            $archive->name . '.zip';

        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';
        $client = new Client( array( 'base_uri' => $this->baseURL ) );

        $zipDeployEndpoint =
            '/api/v1/sites/' .
            $this->siteID .
            '/deploys';

        try {
            $response = $client->request(
                'POST',
                $zipDeployEndpoint,
                array(
                    'headers'  => array(
                        'Content-Type' => 'application/zip',
                        'Authorization' => 'Bearer ' .
                            $this->settings['netlifyPersonalAccessToken'],
                    ),
                    'body' => fopen( $zipArchivePath, 'rb' ),
                )
            );

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'NETLIFY EXPORT ERROR' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }
    }

    public function test_netlify() {
        require_once dirname( __FILE__ ) .
            '/../library/GuzzleHttp/autoloader.php';

        $client = new Client( array( 'base_uri' => $this->baseURL ) );

        $site_info_endpoint =
            '/api/v1/sites/' .
            $this->siteID;

        try {
            $response = $client->request(
                'GET',
                $site_info_endpoint,
                array(
                    'headers'  => array(
                        'Authorization' => 'Bearer ' .
                            $this->settings['netlifyPersonalAccessToken'],
                    ),
                )
            );

            $response_elements =
                json_decode( (string) $response->getBody(), true );

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
            error_log( $e );
            throw new Exception( $e );
        }
    }
}

$netlify = new StaticHtmlOutput_Netlify();
