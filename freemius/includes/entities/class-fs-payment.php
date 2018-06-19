<?php
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2016, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       1.0.0
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class FS_Payment extends FS_Entity {

        #region Properties

        /**
         * @var number
         */
        public $plugin_id;
        /**
         * @var number
         */
        public $user_id;
        /**
         * @var number
         */
        public $install_id;
        /**
         * @var number
         */
        public $subscription_id;
        /**
         * @var number
         */
        public $plan_id;
        /**
         * @var number
         */
        public $license_id;
        /**
         * @var float
         */
        public $gross;
        /**
         * @var number
         */
        public $bound_payment_id;
        /**
         * @var string
         */
        public $external_id;
        /**
         * @var string
         */
        public $gateway;
        /**
         * @var string ISO 3166-1 alpha-2 - two-letter country code.
         *
         * @link http://www.wikiwand.com/en/ISO_3166-1_alpha-2
         */
        public $country_code;
        /**
         * @var string
         */
        public $vat_id;
        /**
         * @var float Actual Tax / VAT in $$$
         */
        public $vat;
        /**
         * @var int Payment source.
         */
        public $source = 0;

        #endregion Properties

        /**
         * @param object|bool $payment
         */
        function __construct( $payment = false ) {
            parent::__construct( $payment );
        }

        static function get_type() {
            return 'payment';
        }

        /**
         * @author Vova Feldman (@svovaf)
         * @since  1.0.0
         *
         * @return bool
         */
        function is_refund() {
            return ( parent::is_valid_id( $this->bound_payment_id ) && 0 > $this->gross );
        }

        /**
         * Checks if the payment was migrated from another platform.
         *
         * @author Vova Feldman (@svovaf)
         * @since  2.0.2
         *
         * @return bool
         */
        function is_migrated() {
            return ( 0 != $this->source );
        }
    }