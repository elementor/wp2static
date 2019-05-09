<?php

namespace WP2Static;

class Base {

    public function loadSettings() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );

        // TODO: move into Options class
        /*
            Settings requiring transformation
        */

        //    // @codingStandardsIgnoreStart
        //    $settings['crawl_increment'] =
        //        isset( $_POST['crawl_increment'] ) ?
        //        (int) $_POST['crawl_increment'] :
        //        1;

        //    // any baseUrl required if creating an offline ZIP
        //    // use original WP siteURL whe no other base URL set
        //    $settings['baseUrl'] =
        //        isset( $_POST['baseUrl'] ) ?
        //        rtrim( $_POST['baseUrl'], '/' ) . '/' :
        //        $_POST['site_url'];
        //    // @codingStandardsIgnoreEnd
    }

    public function ruleSort( $str1, $str2 ) {
        return 0 - strcmp( $str1, $str2 );
    }
}

