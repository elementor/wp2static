<?php

class StaticHtmlOutput_Netlify extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $this->loadSettings( 'netlify' );

        $this->settings['netlifySiteID'];
        $this->settings['netlifyPersonalAccessToken'];
        $this->base_url = 'https://api.netlify.com';

        $this->detectSiteID();

        if ( defined( 'WP_CLI' ) ) { return; }

        switch ( $_POST['ajax_action'] ) {
            case 'test_netlify':
                $this->loadArchive();
                $this->test_netlify();
                break;
            case 'netlify_do_export':
                $this->bootstrap();
                $this->loadArchive();
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
        $this->zip_archive_path = $this->settings['wp_uploads_path'] . '/' .
            $this->archive->name . '.zip';

        $zip_deploy_endpoint = $this->base_url . '/api/v1/sites/' .
            $this->site_id . '/deploys';

        try {
            $headers = array(
                'Authorization: Bearer ' .
                    $this->settings['netlifyPersonalAccessToken'],
                'Content-Type: application/zip',
            );

            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/Request.php';
            $this->client = new WP2Static_Request();

            $this->client->postWithFileStreamAndHeaders(
                $zip_deploy_endpoint,
                $this->zip_archive_path,
                $headers
            );

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );

            $this->finalizeDeployment();
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }
    }

    public function test_netlify() {
        $this->zip_archive_path = $this->settings['wp_uploads_path'] . '/' .
            $this->archive->name . '.zip';

        $site_info_endpoint = $this->base_url . '/api/v1/sites/' .
            $this->site_id;

        try {

            $headers = array(
                'Authorization: Bearer ' .
                    $this->settings['netlifyPersonalAccessToken'],
            );

            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/Request.php';
            $this->client = new WP2Static_Request();

            $this->client->getWithCustomHeaders(
                $site_info_endpoint,
                $headers
            );

            $this->checkForValidResponses(
                $this->client->status_code,
                array( '200', '201', '301', '302', '304' )
            );
        } catch ( Exception $e ) {
            $this->handleException( $e );
        }
    }
}

$netlify = new StaticHtmlOutput_Netlify();
