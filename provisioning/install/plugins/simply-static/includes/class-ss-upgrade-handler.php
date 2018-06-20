<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Upgrade Handler class
 *
 * Used for handling upgrades/downgrades of Simply Static
 */
class Upgrade_Handler {

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected static $options = null;

	/**
	 * Default options to set for the plugin
	 * @var array
	 */
	protected static $default_options = null;

	/**
	 * Disable usage of "new"
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Disable cloning of the class
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Disable unserializing of the class
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Create settings and setup database
	 * @return void
	 */
	public static function run() {
		self::$options = Options::instance();
		self::$default_options = array(
			'destination_scheme' => Util::origin_scheme(),
			'destination_host' => Util::origin_host(),
			'temp_files_dir' => trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) . 'static-files' ),
			'additional_urls' => '',
			'additional_files' => '',
			'urls_to_exclude' => array(),
			'delivery_method' => 'zip',
			'local_dir' => '',
			'delete_temp_files' => '1',
			'relative_path' => '',
			'destination_url_type' => 'relative',
			'archive_status_messages' => array(),
			'archive_name' => null,
			'archive_start_time' => null,
			'archive_end_time' => null,
			'debugging_mode' => '0',
			'http_basic_auth_digest' => null,
		);

		$save_changes = false;
		$version = self::$options->get( 'version' );

		// Never installed or options key changed
		if ( null === $version ) {
			$save_changes = true;

			// checking for legacy options key
			$old_ss_options = get_option( 'simply_static' );

			if ( $old_ss_options ) { // options key changed
				update_option( 'simply-static', $old_ss_options );
				delete_option( 'simply_static' );

				// update Simply_Static\Options again to pull in updated data
				self::$options = new Options();
			}
		}

		// sync the database on any install/upgrade/downgrade
		if ( version_compare( $version, Plugin::VERSION, '!=' ) ) {
			$save_changes = true;

			Page::create_or_update_table();
			self::set_default_options();

			// perform migrations if our saved version # is older than
			// the current version
			if ( version_compare( $version, Plugin::VERSION, '<' ) ) {

				if ( version_compare( $version, '1.4.0', '<' ) ) {
					// check for, and add, the WP emoji url if it's missing
					$emoji_url = includes_url( 'js/wp-emoji-release.min.js' );
					$additional_urls = self::$options->get( 'additional_urls' );
					$urls_array = Util::string_to_array( $additional_urls );

					if ( ! in_array( $emoji_url, $urls_array ) ) {
						$additional_urls = $additional_urls . "\n"  . $emoji_url;
						self::$options->set( 'additional_urls', $additional_urls );
					}
				}

				if ( version_compare( $version, '1.7.0', '<' ) ) {
					$scheme = self::$options->get( 'destination_scheme' );
					if ( strpos( $scheme, '://' ) === false ) {
						$scheme = $scheme . '://';
						self::$options->set( 'destination_scheme', $scheme );
					}

					$host = self::$options->get( 'destination_host' );
					if ( $host == Util::origin_host() ) {
						self::$options->set( 'destination_url_type', 'relative' );
					} else {
						self::$options->set( 'destination_url_type', 'absolute' );
					}
				}

				if ( version_compare( $version, '1.7.1', '<' ) ) {
					// check for, and add, the WP uploads dir if it's missing
					$upload_dir = wp_upload_dir();
					if ( isset( $upload_dir['basedir'] ) ) {
						$upload_dir = trailingslashit( $upload_dir['basedir'] );

						$additional_files = self::$options->get( 'additional_files' );
						$files_array = Util::string_to_array( $additional_files );

						if ( ! in_array( $upload_dir, $files_array ) ) {
							$additional_files = $additional_files . "\n" . $upload_dir;
							self::$options->set( 'additional_files', $additional_files );
						}
					}
				}

				// setting the temp dir back to the one within /simply-static/
				if ( version_compare( $version, '2.0.4', '<' ) ) {
					$old_tmp_dir = trailingslashit( trailingslashit( get_temp_dir() ) . 'static-files' );
					if ( self::$options->get( 'temp_files_dir' ) === $old_tmp_dir ) {
						self::$options->set( 'temp_files_dir', self::$default_options['temp_files_dir'] );
					}
				}
			}

			self::remove_old_options();
		}

		if ( $save_changes ) {
			// update the version and save options
			self::$options
				->set( 'version', Plugin::VERSION )
				->save();
		}
	}

	/**
	 * Add default options where they don't exist
	 * @return void
	 */
	protected static function set_default_options() {
		foreach ( self::$default_options as $option_key => $option_value ) {
			if ( self::$options->get( $option_key ) === null ) {
				self::$options->set( $option_key, $option_value );
			}
		}
	}

	/**
	 * Remove any unused (old) options
	 * @return void
	 */
	protected static function remove_old_options() {
		$all_options = self::$options->get_as_array();

		foreach ( $all_options as $option_key => $option_value ) {
			if ( ! array_key_exists( $option_key, self::$default_options ) ) {
				self::$options->destroy( $option_key );
			}
		}
	}
}
