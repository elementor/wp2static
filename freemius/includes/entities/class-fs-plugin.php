<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2015, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.0.3
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class FS_Plugin extends FS_Scope_Entity {
        /**
         * @since 1.0.6
         * @var null|number
         */
        public $parent_plugin_id;
        /**
         * @var string
         */
        public $title;
        /**
         * @var string
         */
        public $slug;
        /**
         * @since 1.2.2
         *
         * @var string 'plugin' or 'theme'
         */
        public $type;
        /**
         * @author Leo Fajardo (@leorw)
         *
         * @since  1.2.3
         *
         * @var string|false false if the module doesn't have an affiliate program or one of the following: 'selected', 'customers', or 'all'.
         */
        public $affiliate_moderation;
        /**
         * @var bool Set to true if the free version of the module is hosted on WordPress.org. Defaults to true.
         */
        public $is_wp_org_compliant = true;

        #region Install Specific Properties

        /**
         * @var string
         */
        public $file;
        /**
         * @var string
         */
        public $version;
        /**
         * @var bool
         */
        public $auto_update;
        /**
         * @var FS_Plugin_Info
         */
        public $info;
        /**
         * @since 1.0.9
         *
         * @var bool
         */
        public $is_premium;
        /**
         * @since 1.0.9
         *
         * @var bool
         */
        public $is_live;

        const AFFILIATE_MODERATION_CUSTOMERS = 'customers';

        #endregion Install Specific Properties

        /**
         * @param stdClass|bool $plugin
         */
        function __construct( $plugin = false ) {
            parent::__construct( $plugin );

            $this->is_premium = false;
            $this->is_live    = true;

            if ( isset( $plugin->info ) && is_object( $plugin->info ) ) {
                $this->info = new FS_Plugin_Info( $plugin->info );
            }
        }

        /**
         * Check if plugin is an add-on (has parent).
         *
         * @author Vova Feldman (@svovaf)
         * @since  1.0.6
         *
         * @return bool
         */
        function is_addon() {
            return isset( $this->parent_plugin_id ) && is_numeric( $this->parent_plugin_id );
        }

        /**
         * @author Leo Fajardo (@leorw)
         * @since  1.2.3
         *
         * @return bool
         */
        function has_affiliate_program() {
            return ( ! empty( $this->affiliate_moderation ) );
        }

        static function get_type() {
            return 'plugin';
        }
    }