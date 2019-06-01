<?php

namespace WP2Static;

class Base {

    public $settings;

    public function loadSettings() : void {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    public function ruleSort( string $str1, string $str2 ) : int {
        return 0 - strcmp( $str1, $str2 );
    }
}

