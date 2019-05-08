<?php

namespace WP2Static;

class Base {

    public function loadSettings( $target_settings ) {
        $general_settings = array(
            'general',
        );

        $target_settings = array_merge(
            $general_settings,
            $target_settings
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            $this->settings = PostSettings::get( $target_settings );
        } else {
            $this->settings = DBSettings::get( $target_settings );
        }
    }

    public function ruleSort( $str1, $str2 ) {
        return 0 - strcmp( $str1, $str2 );
    }
}

