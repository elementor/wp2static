<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {
	public $warnings = array();
	public $notices = array();
	public $real_error_handler = array();

	function init() {
		if ( ! WP_DEBUG ) {
			return false;
		}

		$this->title( __( 'Notices / Warnings', 'debug-bar' ) );

		$this->real_error_handler = set_error_handler( array( &$this, 'error_handler' ) );
	}

	function is_visible() {
		return count( $this->notices ) || count( $this->warnings );
	}

	function debug_bar_classes( $classes ) {
		if ( count( $this->warnings ) ) {
			$classes[] = 'debug-bar-php-warning-summary';
		} elseif ( count( $this->notices ) ) {
			$classes[] = 'debug-bar-php-notice-summary';
		}

		return $classes;
	}

	function error_handler( $type, $message, $file, $line ) {
		$_key = md5( $file . ':' . $line . ':' . $message );

		if ( ! defined( 'E_DEPRECATED' ) ) {
			define( 'E_DEPRECATED', 8192 );
		}
		if ( ! defined( 'E_USER_DEPRECATED' ) ) {
			define( 'E_USER_DEPRECATED', 16384 );
		}

		switch ( $type ) {
			case E_WARNING:
			case E_USER_WARNING:
				$this->warnings[ $_key ] = array(
					$file . ':' . $line,
					$message,
					wp_debug_backtrace_summary( __CLASS__ ),
				);
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$this->notices[ $_key ] = array(
					$file . ':' . $line,
					$message,
					wp_debug_backtrace_summary( __CLASS__ ),
				);
				break;
			case E_STRICT:
				// TODO
				break;
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				// TODO
				break;
			case 0:
				// TODO
				break;
		}

		if ( null != $this->real_error_handler ) {
			return call_user_func( $this->real_error_handler, $type, $message, $file, $line );
		} else {
			return false;
		}
	}

	function render() {
		echo "<div id='debug-bar-php'>";
		echo '<h2><span>', __( 'Total Warnings:', 'debug-bar' ), '</span>', number_format_i18n( count( $this->warnings ) ), "</h2>\n";
		echo '<h2><span>', __( 'Total Notices:', 'debug-bar' ), '</span>', number_format_i18n( count( $this->notices ) ), "</h2>\n";

		if ( count( $this->warnings ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $this->warnings as $location_message_stack ) {
				list( $location, $message, $stack ) = $location_message_stack;
				echo '<li class="debug-bar-php-warning">', __( 'WARNING:', 'debug-bar' ), ' ';
				echo str_replace( ABSPATH, '', $location ) . ' - ' . strip_tags( $message );
				echo '<br/>';
				echo $stack;
				echo '</li>';
			}
			echo '</ol>';
		}

		if ( count( $this->notices ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $this->notices as $location_message_stack ) {
				list( $location, $message, $stack ) = $location_message_stack;
				echo '<li class="debug-bar-php-notice">', __( 'NOTICE:', 'debug-bar' ), ' ';
				echo str_replace( ABSPATH, '', $location ) . ' - ' . strip_tags( $message );
				echo '<br/>';
				echo $stack;
				echo '</li>';
			}
			echo '</ol>';
		}

		echo "</div>";
	}
}
