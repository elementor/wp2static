<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2018, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       2.0.0
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Plugin_Tag extends FS_Entity {
        /**
         * @var string
         */
		public $version;
        /**
         * @var string
         */
		public $url;
        /**
         * @var string
         */
        public $requires_platform_version;
        /**
         * @var string
         */
        public $tested_up_to_version;
        /**
         * @var bool
         */
        public $has_free;
        /**
         * @var bool
         */
        public $has_premium;
        /**
         * @var bool
         */
        public $is_released;

		function __construct( $tag = false ) {
			parent::__construct( $tag );
		}

		static function get_type() {
			return 'tag';
		}
	}