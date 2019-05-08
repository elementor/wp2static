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
    public static function generate(
        $plugin_rules,
        $user_rules,
        $placeholder_url,
        $base_url
    ) {

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
            $placeholder_url,
            addcslashes( $placeholder_url, '/' ),
            URLHelper::getProtocolRelativeURL(
                $placeholder_url
            ),
            URLHelper::getProtocolRelativeURL(
                $placeholder_url
            ),
            URLHelper::getProtocolRelativeURL(
                $placeholder_url . '/'
            ),
            URLHelper::getProtocolRelativeURL(
                addcslashes( $placeholder_url, '/' )
            ),
        );


        $plugin_rules['replace_patterns'] = array(
            $base_url,
            addcslashes( $base_url, '/' ),
            URLHelper::getProtocolRelativeURL(
                $base_url
            ),
            URLHelper::getProtocolRelativeURL(
                rtrim( $base_url, '/' )
            ),
            URLHelper::getProtocolRelativeURL(
                $base_url . '//'
            ),
            URLHelper::getProtocolRelativeURL(
                addcslashes( $base_url, '/' )
            ),
        );

        // if no user defined rewrite rules, init empty string for building
        if ( ! isset( $user_rules ) ) {
            $user_rules = '';
        }

        $placeholder_url = rtrim( $placeholder_url, '/' );
        $destination_url = rtrim(
            $base_url,
            '/'
        );

        // add base URL to rewrite_rules
        $user_rules .=
            PHP_EOL .
                $placeholder_url . ',' .
                $destination_url;

        $rewrite_from = array();
        $rewrite_to = array();

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $user_rules )
        );

        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        uksort(
            $tmp_rules,
            function ( $str1, $str2 ) {
                return 0 - strcmp( $str1, $str2 );
            }
        );

        foreach ( $tmp_rules as $from => $to ) {
            $rewrite_from[] = $from;
            $rewrite_to[] = $to;
        }
    }
}
