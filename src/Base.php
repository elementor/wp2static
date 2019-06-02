<?php

namespace WP2Static;

class Base {

    public $settings;

    public function loadSettings() : void {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }
}

