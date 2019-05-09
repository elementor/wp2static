<?php

namespace WP2Static;

class Base {

    public function loadSettings() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function ruleSort( $str1, $str2 ) {
        return 0 - strcmp( $str1, $str2 );
    }
}

