<?php

namespace WP2Static;

class GenerateRewriteRules {

    /*
     * We only peform rewrites against our placeholder URLs
     * We combine our rules with user-defined rewrites and perform all at once 
     *
     * @param array $plugin_rules our default placeholder -> destination rules
     * @param array $user_rules user's path rewriting rules 
     * @return array combining search and replacement rules 
     */
    public static function generate( $plugin_rules, $user_rules ) {

        /*
         * Pseudo steps:
         * 
         * get plugin's rules 
         * get user rules 
         * combine into full URLs for replacement 
         * add escaped versions of the URLs 
         * return  
         *
         */ 
        
        $plugin_rules['search_patterns'] = array(
            $this->placeholder_url,
            addcslashes( $this->placeholder_url, '/' ),
            $this->getProtocolRelativeURL(
                $this->placeholder_url
            ),
            $this->getProtocolRelativeURL(
                $this->placeholder_url
            ),
            $this->getProtocolRelativeURL(
                $this->placeholder_url . '/'
            ),
            $this->getProtocolRelativeURL(
                addcslashes( $this->placeholder_url, '/' )
            ),
        );


        $plugin_rules['replace_patterns'] = array(
            $this->settings['baseUrl'],
            addcslashes( $this->settings['baseUrl'], '/' ),
            $this->getProtocolRelativeURL(
                $this->settings['baseUrl']
            ),
            $this->getProtocolRelativeURL(
                rtrim( $this->settings['baseUrl'], '/' )
            ),
            $this->getProtocolRelativeURL(
                $this->settings['baseUrl'] . '//'
            ),
            $this->getProtocolRelativeURL(
                addcslashes( $this->settings['baseUrl'], '/' )
            ),
        );

        // if no user defined rewrite rules, init empty string for building
        if ( ! isset( $this->settings['rewrite_rules'] ) ) {
            $this->settings['rewrite_rules'] = '';
        }

        $placeholder_url = rtrim( $this->placeholder_url, '/' );
        $destination_url = rtrim(
            $this->settings['baseUrl'],
            '/'
        );

        // add base URL to rewrite_rules
        $this->settings['rewrite_rules'] .=
            PHP_EOL .
                $placeholder_url . ',' .
                $destination_url;

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $this->settings['rewrite_rules'] )
        );

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        uksort( $tmp_rules, array( $this, 'ruleSort' ) );

        foreach ( $tmp_rules as $from => $to ) {
            $rewrite_from[] = $from;
            $rewrite_to[] = $to;
        }
    }
}
