<?php

namespace WP2Static;

class RewriteRules {

    /*
     * We combine our rules with user-defined rewrites and perform all at once 
     *
     * @param string $site_url WP site URL
     * @param string $destination_url WP site URL
     * @param array|false $user_rules user's path rewriting rules 
     * @return array combining search and replacement rules for the 3 URL types
     */
    public static function generate(
        $site_url,
        $destination_url
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

        $rewrite_rules = [];
        
        $rewrite_rules['site_url_patterns'] =
            self::generatePatterns( $site_url, $user_rewrite_rules);

        $rewrite_rules['destination_url_patterns'] =
            self::generatePatterns( $destination_url, $user_rewrite_rules );

        return $rewrite_rules;
    }

    /*
     * Generate patterns used for searching / replacing
     *
     * @param string $url URL
     * @param string $user_rewrite_rules csv user-defined rewrite rules
     */
    public static function generatePatterns( $url ) {
        $url = rtrim( $url, '/' );
        $url_with_cslashes = addcslashes( $url, '/' );

        $patterns = array(
            $url,
            $url_with_cslashes,
        );

        return $patterns;
    }

    /*
     * Get user-defined rewrite rules into plugin defaults
     *
     * @param string $user_rewrite_rules csv user-defined rewrite rules
     * @return array patterns including user-defined rewrite rules
     *
     */
    public static function getUserRewriteRules( $user_rewrite_rules ) {
        if ( ! $user_rewrite_rules ) {
            return;
        }

        $rewrite_rules = [];
        $rewrite_rules['from'] = [];
        $rewrite_rules['to'] = [];

        $rewrite_rules = explode(
            "\n",
            str_replace( "\r", '', $user_rewrite_rules )
        );

        // hold all values in transient array
        $tmp_rules = array();

        foreach ( $rewrite_rules as $rewrite_rule_line ) {
            if ( $rewrite_rule_line ) {
                list($from, $to) = explode( ',', $rewrite_rule_line );
                $tmp_rules[ $from ] = $to;
            }
        }

        // sort the transient array by longest path first to help users
        // not worry about order of input
        uksort(
            $tmp_rules,
            function ( $str1, $str2 ) {
                return 0 - strcmp( $str1, $str2 );
            }
        );

        foreach ( $tmp_rules as $from => $to ) {
            $rewrite_rules['from'] = $from;
            $rewrite_rules['to'] = $to;
        }

        return $rewrite_rules;
    }
}
