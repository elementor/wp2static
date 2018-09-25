<?php
/**
 * StaticHtmlOutput_Netlify
 *
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

use GuzzleHttp\Client;

class StaticHtmlOutput_Netlify {

    protected $_siteID;
    protected $_personalAccessToken;
    protected $_baseURL;


    /**
     * Constructor
     *
     * @param string $siteID              Site ID
     * @param string $personalAccessToken Access token
     */
    public function __construct( $siteID, $personalAccessToken ) {
        $this->_siteID = $siteID;
        $this->_personalAccessToken = $personalAccessToken;
        $this->_baseURL = 'https://api.netlify.com';
    }


    /**
     * Deploy
     *
     * @param string $zipArchivePath Archive path
     * @return string
     * @throws Exception If unable to export
     */
    public function deploy( $zipArchivePath ) {
        require_once dirname( __FILE__ ) . '/../GuzzleHttp/autoloader.php';

        $client = new Client( array( 'base_uri' => $this->_baseURL ) );

        $zipDeployEndpoint = '/api/v1/sites/' . $this->_siteID .
            '.netlify.com/deploys';

        try {
            $authorization = 'Bearer ' . $this->_personalAccessToken;
            $response = $client->request(
                'POST', $zipDeployEndpoint, array(
                    'headers'  => array(
                        'Content-Type' => 'application/zip',
                        'Authorization' => $authorization,
                    ),
                    'body' => fopen( $zipArchivePath, 'rb' ),
                )
            );

            return 'SUCCESS';

        } catch ( Exception $e ) {
            WsLog::l( 'NETLIFY EXPORT ERROR' );
            WsLog::l( $e );
            error_log( $e );
            throw new Exception( $e );
        }
    }
}

