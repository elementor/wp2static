<?php
/*
    XHRRouter

    Route requests made in the UI to the appropriate server-side methods
*/

namespace WP2Static;

class XHRRouter {

    private $plugin_instance;

    /**
     * XHRRouter constructor
     *
     * @param Controller $plugin_instance plugin instance
     */
    public function __construct(Controller $plugin_instance) {
        $this->plugin_instance = $plugin_instance;
    }

    /**
     * Resgister known XHR routes
     *
     * Listens for XHR's and routes to server-side Class::method
     *
     */
    public function registerXHRRoutes() : void {
        $ajax_action = filter_input( INPUT_POST, 'ajax_action' );

        // TODO where best to check nonces
        // check_ajax_referer( $ajax_action, 'nonce' );

        if ( $ajax_action === 'reset_default_settings' ) {
            $instance->reset_default_settings();
        }


    }
}

