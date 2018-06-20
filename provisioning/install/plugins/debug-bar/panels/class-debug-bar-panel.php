<?php

class Debug_Bar_Panel {
	public $_title = '';
	public $_visible = true;

	function __construct( $title = '' ) {
		$this->title( $title );

		if ( $this->init() === false ) {
			$this->set_visible( false );

			return;
		}

		add_filter( 'debug_bar_classes', array( $this, 'debug_bar_classes' ) );
	}

	function Debug_Bar_Panel( $title = '' ) {
		_deprecated_constructor( __METHOD__, '0.8.3', __CLASS__ );
		self::__construct( $title );
	}

	/**
	 * Initializes the panel.
	 */
	function init() {}

	function prerender() {}

	/**
	 * Renders the panel.
	 */
	function render() {}

	function is_visible() {
		return $this->_visible;
	}

	function set_visible( $visible ) {
		$this->_visible = $visible;
	}

	/**
	 * Get/set title.
	 *
	 * @param null $title
	 * @return string|void
	 */
	function title( $title = null ) {
		if ( ! isset( $title ) ) {
			return $this->_title;
		}

		$this->_title = $title;
	}

	function debug_bar_classes( $classes ) {
		return $classes;
	}
}
