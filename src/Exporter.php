<?php

namespace WP2Static;

class Exporter {

    private $settings;

    public function __construct() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function pre_export_cleanup() : void {
        global $wpdb;

        $tables_to_truncate = [
            //'wp2static_urls',
            'wp2static_export_log',
        ];

        $errors = 0;

        foreach ( $tables_to_truncate as $table ) {
            $table_name = $wpdb->prefix . $table;

            $wpdb->query( "TRUNCATE TABLE $table_name" );

            $sql =
                "SELECT count(*) FROM $table_name";

            $count = $wpdb->get_var( $sql );

            if ( $count === '0' ) {
                $errors++;
            }
        }

        if ( $errors === '0' ) {
            http_response_code( 200 );

            echo 'SUCCESS';
        } else {
            http_response_code( 500 );
        }
    }

    public function generateModifiedFileList() : void {
        // have initial crawl list in wp2static-urls with type 'detected'

        $modified_crawl_list = array();

        // add each exclusion to wp2static-urls with type 'exclude'
        if ( isset( $this->settings['excludeURLs'] ) ) {
            $exclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['excludeURLs'] )
            );

            foreach ( $exclusions as $exclusion ) {
                $exclusion = trim( $exclusion );

                // TODO: add to DB
            }
        } 

        // add each additional URL 
        if ( isset( $this->settings['additionalUrls'] ) ) {
            $inclusions = explode(
                "\n",
                str_replace( "\r", '', $this->settings['additionalUrls'] )
            );

            foreach ( $inclusions as $inclusion ) {
                $inclusion = trim( $inclusion );

                // TODO: add to DB
            }
        }

        // we now have modified file list in DB
    }
}

