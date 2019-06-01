<?php

namespace WP2Static;

class RewriteRules {
    /**
     * Combine our rules with user-defined rewrites and perform all at once
     *
     * @return mixed[] combined search and replacement rules
     */
    public static function generate(
        string $site_url,
        string $destination_url
    ) : array {
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
            self::generatePatterns( $site_url );

        $rewrite_rules['destination_url_patterns'] =
            self::generatePatterns( $destination_url );

        return $rewrite_rules;
    }

    /**
     * Generate patterns used for searching / replacing
     *
     * @return string[]] rewrite rules
     */
    public static function generatePatterns( string $url ) : array {
        $url = rtrim( $url, '/' );
        $url_with_cslashes = addcslashes( $url, '/' );

        $patterns = array(
            $url,
            $url_with_cslashes,
        );

        return $patterns;
    }

    /**
     * Get user-defined rewrite rules into plugin defaults
     *
     * @param string $user_rewrite_rules csv user-defined rewrite rules
     * @return mixed[] patterns including user-defined rewrite rules
     */
    public static function getUserRewriteRules(
        string $user_rewrite_rules
    ) : array {
        if ( ! $user_rewrite_rules ) {
            return [];
        }

        $rewrite_rules_output = [];
        $rewrite_rules_output['from'] = [];
        $rewrite_rules_output['to'] = [];

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
            $rewrite_rules_output['from'][] = $from;
            $rewrite_rules_output['to'][] = $to;
        }

        return $rewrite_rules_output;
    }
}
