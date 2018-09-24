<?php
/**
 * Freemius_InvalidArgumentException
 *
 * @package WP2Static
 */

	if ( ! class_exists( 'Freemius_Exception' ) ) {
		exit;
	}

	if ( ! class_exists( 'Freemius_InvalidArgumentException' ) ) {
		class Freemius_InvalidArgumentException extends Freemius_Exception { }
	}