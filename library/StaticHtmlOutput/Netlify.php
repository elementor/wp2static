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
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            error_log( 'TODO: load settings from DB' );
        }

        $this->settings['netlifySiteID'];
        $this->settings['netlifyPersonalAccessToken'];
        $this->baseURL = 'https://api.netlify.com';
        $site_id = $this->settings['netlifySiteID'];

        if ( strpos( $site_id, 'netlify.com' ) !== false ) {
            // fuly qualified site detected
            // ie, blah.netlify.com
        } elseif ( strpos( $site_id, '.' ) !== false ) {
            // assume fuly qualified site detected
            // ie, mysite.com
        } elseif ( strlen( $site_id ) === 37 ) {
            // assume API ID for site/hash
        } else {
            // netlify site id only, let's prepend .netlify.com
            $site_id .= '.netlify.com';
        }

        $this->siteID = $site_id;

        switch ( $_POST['ajax_action'] ) {
            case 'test_netlify':
                $this->test_netlify();
                break;
           // case 'bitbucket_upload_files':
           //     $this->upload_files();
           //     break;
           // case 'test_bitbucket':
           //     $this->test_blob_create();
           //     break;
        }
    }

    public function deploy( $zipArchivePath ) {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

        $client = new Client( array( 'base_uri' => $this->baseURL ) );

        $zipDeployEndpoint =
            '/api/v1/sites/' .
            $this->siteID;

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

            return 'SUCCESS';

        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'NETLIFY EXPORT ERROR' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }
    }

    public function test_netlify() {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

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
                json_decode((string) $response->getBody(), true);

            if ( isset( $response_elements['updated_at'] ) ) {
                echo 'Last updated at: '. $response_elements['updated_at'];
            } else {
                echo 'Looks like this is your first time deploying this site on Netlify - good luck!';
            }

        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/WsLog.php';
            WsLog::l( 'NETLIFY TEST ERROR' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }
    }
}

$netlify = new StaticHtmlOutput_Netlify();
