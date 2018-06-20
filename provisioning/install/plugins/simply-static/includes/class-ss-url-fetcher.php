<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static URL fetcher class
 */
class Url_Fetcher {
	/**
	 * Timeout for fetching URLs
	 * @var string
	 */
	const TIMEOUT = 30;

	/**
	 * Singleton instance
	 * @var Simply_Static\Url_Fetcher
	 */
	protected static $instance = null;

	/**
	 * Directory to save the body of the URL to
	 * @var string
	 */
	protected $archive_dir = null;

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
	 * Return an instance of Simply_Static\Url_Fetcher
	 * @return Simply_Static
	 */
	public static function instance()
	{
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->archive_dir = Options::instance()->get_archive_dir();
		}

		return self::$instance;
	}

	/**
	 * Fetch the URL and return a \WP_Error if we get one, otherwise a Response class.
	 * @param Simply_Static\Page $static_page URL to fetch
	 * @return boolean                        Was the fetch successful?
	 */
	public function fetch( Page $static_page ) {
		$url = $static_page->url;

		$static_page->last_checked_at = Util::formatted_datetime();

		// Don't process URLs that don't match the URL of this WordPress installation
		if ( ! Util::is_local_url( $url ) ) {
			Util::debug_log( "Not fetching URL because it is not a local URL" );
			$static_page->http_status_code = null;
			$message = sprintf( __( "An error occurred: %s", 'simply-static' ), __( "Attempted to fetch a remote URL", 'simply-static' ) );
			$static_page->set_error_message( $message );
			$static_page->save();
			return false;
		}

		$temp_filename = wp_tempnam();

		Util::debug_log( "Fetching URL and saving it to: " . $temp_filename );
		$response = self::remote_get( $url, $temp_filename );

		$filesize = file_exists( $temp_filename ) ? filesize( $temp_filename ) : 0;
		Util::debug_log( "Filesize: " . $filesize . ' bytes' );

		if ( is_wp_error( $response ) ) {
			Util::debug_log( "We encountered an error when fetching: " . $response->get_error_message() );
			Util::debug_log( $response );
			$static_page->http_status_code = null;
			$message = sprintf( __( "An error occurred: %s", 'simply-static' ), $response->get_error_message() );
			$static_page->set_error_message( $message );
			$static_page->save();
			return false;
		} else {
			$static_page->http_status_code = $response['response']['code'];
			$static_page->content_type = $response['headers']['content-type'];
			$static_page->redirect_url = isset( $response['headers']['location'] ) ? $response['headers']['location'] : null;

			Util::debug_log( "http_status_code: " . $static_page->http_status_code . " | content_type: " . $static_page->content_type  );

			$relative_filename = null;
			if ( $static_page->http_status_code == 200 ) {
				// pclzip doesn't like 0 byte files (fread error), so we're
				// going to fix that by putting a single space into the file
				if ( $filesize === 0 ) {
					file_put_contents( $temp_filename, ' ' );
				}

				$relative_filename = $this->create_directories_for_static_page( $static_page );
			}

			if ( $relative_filename !== null ) {
				$static_page->file_path = $relative_filename;
				$file_path = $this->archive_dir . $relative_filename;
				Util::debug_log( "Renaming temp file from " . $temp_filename . " to " . $file_path );
				rename( $temp_filename, $file_path );
			} else {
				Util::debug_log( "We weren't able to establish a filename; deleting temp file" );
				unlink( $temp_filename );
			}

			$static_page->save();

			return true;
		}
	}

	/**
	 * Given a Static_Page, return a relative filename based on the URL
	 *
	 * This will also create directories as needed so that a file could be
	 * created at the returned file path.
	 *
	 * @param Simply_Static\Page $static_page The Simply_Static\Page
	 * @return string|null                The relative file path of the file
	 */
	public function create_directories_for_static_page( $static_page ) {
		$url_parts = parse_url( $static_page->url );
		// a domain with no trailing slash has no path, so we're giving it one
		$path = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';

		$origin_path_length = strlen( parse_url( Util::origin_url(), PHP_URL_PATH ) );
		if ( $origin_path_length > 1 ) { // prevents removal of '/'
			$path = substr( $path, $origin_path_length );
		}

		$path_info = Util::url_path_info( $path );

		$relative_file_dir = $path_info['dirname'];
		$relative_file_dir = Util::remove_leading_directory_separator( $relative_file_dir );

		// If there's no extension, we're going to create a directory with the
		// filename and place an index.html/xml file in there.
		if ( $path_info['extension'] === '' ) {
			if ( $path_info['filename'] !== '' ) {
				// the filename would be blank for the root url, in that
				// instance we don't want to add an extra slash
				$relative_file_dir .= $path_info['filename'];
				$relative_file_dir = Util::add_trailing_directory_separator( $relative_file_dir );
			}
			$path_info['filename'] = 'index';
			if ( $static_page->is_type( 'xml' ) ) {
				$path_info['extension'] = 'xml';
			} else {
				$path_info['extension'] = 'html';
			}
		}

		$create_dir = wp_mkdir_p( $this->archive_dir . $relative_file_dir );
		if ( $create_dir === false ) {
			Util::debug_log( "Unable to create temporary directory: " . $this->archive_dir . $relative_file_dir );
			$static_page->set_error_message( 'Unable to create temporary directory' );
		} else {
			$relative_filename = $relative_file_dir . $path_info['filename'] . '.' . $path_info['extension'];
			Util::debug_log( "New filename for static page: " . $relative_filename );

			// check that file doesn't exist OR exists but is writeable
			// (generally, we'd expect it to never exist)
			if ( ! file_exists( $relative_filename ) || is_writable( $relative_filename ) ) {
				return $relative_filename;
			} else {
				Util::debug_log( "File exists and is unwriteable" );
				$static_page->set_error_message( 'File exists and is unwriteable' );
			}
		}

		return null;
	}

	public static function remote_get( $url, $filename = null ) {
		$basic_auth_digest = Options::instance()->get( 'http_basic_auth_digest' );

		$args = array(
			'timeout'     => self::TIMEOUT,
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
			'redirection' => 0, // disable redirection
			'blocking'    => true // do not execute code until this call is complete
		);

		if ( $filename ) {
			$args['stream'] = true; // stream body content to a file
			$args['filename'] = $filename;
		}

		if ( $basic_auth_digest ) {
			$args['headers'] = array( 'Authorization' => 'Basic ' . $basic_auth_digest );
		}

		$response = wp_remote_get( $url, $args );
		return $response;
	}

}
