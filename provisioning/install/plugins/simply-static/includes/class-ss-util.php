<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static utility class
 */
class Util {

	/**
	* Get the protocol used for the origin URL
	* @return string http or https
	*/
	public static function origin_scheme() {
		$pattern = '/:\/\/.*/';
		return preg_replace( $pattern, '', self::origin_url() );
	}

	/**
	* Get the host for the origin URL
	* @return string host (URL minus the protocol)
	*/
	public static function origin_host() {
		return untrailingslashit( self::strip_protocol_from_url( self::origin_url() ) );
	}

	/**
	 * Wrapper around home_url(). Useful for swapping out the URL during debugging.
	 * @return string home URL
	 */
	public static function origin_url() {
		return home_url();
	}

	/**
	 * Wrapper around site_url(). Returns the URL used for the WP installation.
	 * @return string home URL
	 */
	public static function wp_installation_url() {
		return site_url();
	}

	/**
	 * Echo the selected value for an option tag if the statement is true.
	 * @return null
	 */
	public static function selected_if( $statement ) {
		echo ( $statement == true ? 'selected="selected"' : '' );
	}

	/**
	 * Echo the checked value for an input tag if the statement is true.
	 * @return null
	 */
	public static function checked_if( $statement ) {
		echo ( $statement == true ? 'checked="checked"' : '' );
	}

	/**
	 * Truncate if a string exceeds a certain length (30 chars by default)
	 * @return string
	 */
	public static function truncate( $string, $length = 30, $omission = '...' ) {
		return ( strlen( $string ) > $length + 3 ) ? ( substr( $string, 0, $length ) . $omission ) : $string;
	}

	/**
	 * Use trailingslashit unless the string is empty
	 * @return string
	 */
	public static function trailingslashit_unless_blank( $string ) {
		return $string === '' ? $string : trailingslashit( $string );
	}

	/**
	 * Dump an object to error_log
	 * @param mixed $object Object to dump to the error log
	 * @return void
	 */
	public static function error_log( $object = null ) {
		$contents = self::get_contents_from_object( $object );
		error_log( $contents );
	}

	/**
	 * Delete the debug log
	 * @return void
	 */
	public static function delete_debug_log() {
		$debug_file = self::get_debug_log_filename();
		if ( file_exists( $debug_file ) ) {
			unlink( $debug_file );
		}
	}

	/**
	 * Save an object/string to the debug log
	 * @param mixed $object Object to save to the debug log
	 * @return void
	 */
	public static function debug_log( $object = null ) {
		$options = Options::instance();
		if ( $options->get( 'debugging_mode' ) !== '1' ) {
			return;
		}

		$debug_file = self::get_debug_log_filename();

		// add timestamp and newline
		$message = '[' . date( 'Y-m-d H:i:s' ) . '] ';

		$trace = debug_backtrace();
		if ( isset( $trace[0]['file'] ) ) {
			$file = basename( $trace[0]['file'] );
			if ( isset( $trace[0]['line'] ) ) {
				$file .= ':' . $trace[0]['line'];
			}
			$message .= '[' . $file . '] ';
		}

		$contents = self::get_contents_from_object( $object );

		// get message onto a single line
		$contents = preg_replace( "/\r|\n/", "", $contents );

		$message .= $contents . "\n";

		// log the message to the debug file instead of the usual error_log location
		error_log( $message, 3, $debug_file );
	}

	/**
	 * Return the filename for the debug log
	 * @return string Filename for the debug log
	 */
	public static function get_debug_log_filename() {
		return plugin_dir_path( dirname( __FILE__ ) ) . 'debug.txt';
	}

	/**
	 * Get contents of an object as a string
	 * @param  mixed  $object Object to get string for
	 * @return string         String containing the contents of the object
	 */
	protected static function get_contents_from_object( $object ) {
		if ( is_string( $object ) ) {
			return $object;
		}

		ob_start();
		var_dump( $object );
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * Given a URL extracted from a page, return an absolute URL
	 *
	 * Takes a URL (e.g. /test) extracted from a page (e.g. http://foo.com/bar/) and
	 * returns an absolute URL (e.g. http://foo.com/bar/test). Absolute URLs are
	 * returned as-is. Exception: links beginning with a # (hash) are left as-is.
	 *
	 * A null value is returned in the event that the extracted_url is blank or it's
	 * unable to be parsed.
	 *
	 * @param  string       $extracted_url   Relative or absolute URL extracted from page
	 * @param  string       $page_url        URL of page
	 * @return string|null                   Absolute URL, or null
	 */
	public static function relative_to_absolute_url( $extracted_url, $page_url ) {

		$extracted_url = trim( $extracted_url );

		// we can't do anything with blank urls
		if ( $extracted_url === '' ) {
			return null;
		}

		// if we get a hash, e.g. href='#section-three', just return it as-is
		if ( strpos( $extracted_url, '#' ) === 0 ) {
			return $extracted_url;
		}

		// check for a protocol-less URL
		// (Note: there's a bug in PHP <= 5.4.7 where parsed URLs starting with //
		// are treated as a path. So we're doing this check upfront.)
		// http://php.net/manual/en/function.parse-url.php#example-4617
		if ( strpos( $extracted_url, '//' ) === 0 ) {

			// if this is a local URL, add the protocol to the URL
			if ( stripos( $extracted_url, '//' . self::origin_host() ) === 0 ) {
				$extracted_url = self::origin_scheme() . ':' . $extracted_url;
			}

			return $extracted_url;

		}

		$parsed_extracted_url = parse_url( $extracted_url );

		// parse_url can sometimes return false; bail if it does
		if ( $parsed_extracted_url === false ) {
			return null;
		}

		// if no path, check for an ending slash; if there isn't one, add one
		if ( ! isset( $parsed_extracted_url['path'] ) ) {
			$clean_url = self::remove_params_and_fragment( $extracted_url );
			$fragment = substr( $extracted_url, strlen( $clean_url ) );
			$extracted_url = trailingslashit( $clean_url ) . $fragment;
		}

		if ( isset( $parsed_extracted_url['host'] ) ) {

			return $extracted_url;

		} elseif ( isset( $parsed_extracted_url['scheme'] ) ) {

			// examples of schemes without hosts: java:, data:
			return $extracted_url;

		} else { // no host on extracted page (might be relative url)

			$path = isset( $parsed_extracted_url['path'] ) ? $parsed_extracted_url['path'] : '';

			$query = isset( $parsed_extracted_url['query'] ) ? '?' . $parsed_extracted_url['query'] : '';
			$fragment = isset( $parsed_extracted_url['fragment'] ) ? '#' . $parsed_extracted_url['fragment'] : '';

			// turn our relative url into an absolute url
			$extracted_url = \phpUri::parse( $page_url )->join( $path . $query . $fragment );

			return $extracted_url;

		}
	}

	 /**
	  * Recursively create a path from one page to another
	  *
	  * Takes a path (e.g. /blog/foobar/) extracted from a page (e.g. /blog/page/3/)
	  * and returns a path to get to the extracted page from the current page
	  * (e.g. ./../../foobar/index.html). Since this is for offline use, the path
	  * return will include a /index.html if the extracted path doesn't contain
	  * an extension.
	  *
	  * The function recursively calls itself, cutting off sections of the page path
	  * until the base matches the extracted path or it runs out of parts to remove,
	  * then it builds out the path to the extracted page.
	  *
	  * @param  string      $extracted_path Relative or absolute URL extracted from page
	  * @param  string      $page_path      URL of page
	  * @param  int         $iterations     Number of times the page path has been chopped
	  * @return string|null                 Absolute URL, or null
	  */
	public static function create_offline_path( $extracted_path, $page_path, $iterations = 0 ) {
		// We're done if we get a match between the path of the page and the extracted URL
		// OR if there are no more slashes to remove
		if ( strpos( $page_path, '/' ) === false || strpos( $extracted_path, $page_path ) === 0 ) {
			$extracted_path = substr( $extracted_path, strlen( $page_path ) );
			$iterations = ( $iterations == 0 ) ? 0 : $iterations - 1;
			$new_path = '.' . str_repeat( '/..', $iterations ) . self::add_leading_slash( $extracted_path );
			return $new_path;
		} else {
			// match everything before the last slash
			$pattern = '/(.*)\/[^\/]*$/';
			// remove the last slash and anything after it
			$new_page_path = preg_replace( $pattern, '$1', $page_path );
			return self::create_offline_path( $extracted_path, $new_page_path, ++$iterations );
		}
	}

	/**
	 * Check if URL starts with same URL as WordPress installation
	 *
	 * Both http and https are assumed to be the same domain.
	 *
	 * @param  string  $url URL to check
	 * @return boolean      true if URL is local, false otherwise
	 */
	public static function is_local_url( $url ) {
		return ( stripos( self::strip_protocol_from_url( $url ), self::origin_host() ) === 0 );
	}

	/**
	 * Get the path from a local URL, removing the protocol and host
	 * @param  string  $url URL to strip protocol/host from
	 * @return string       URL sans protocol/host
	 */
	public static function get_path_from_local_url( $url ) {
		$url = self::strip_protocol_from_url( $url );
		$url = str_replace( self::origin_host(), '', $url );
		return $url;
	}

	/**
	 * Returns a URL w/o the query string or fragment (i.e. nothing after the path)
	 * @param  string $url URL to remove query string/fragment from
	 * @return string      URL without query string/fragment
	 */
	public static function remove_params_and_fragment( $url ) {
		return preg_replace('/(\?|#).*/', '', $url);
	}

	/**
	 * Converts a textarea into an array w/ each line being an entry in the array
	 * @param  string $textarea Textarea to convert
	 * @return array            Converted array
	 */
	public static function string_to_array( $textarea ) {
		// using preg_split to intelligently break at newlines
		// see: http://stackoverflow.com/questions/1483497/how-to-put-string-in-array-split-by-new-line
		$lines =  preg_split( "/\r\n|\n|\r/", $textarea );
		array_walk( $lines, 'trim' );
		$lines = array_filter( $lines );
		return $lines;
	}

	/**
	 * Remove the //, http://, https:// protocols from a URL
	 * @param  string $url URL to remove protocol from
	 * @return string      URL sans http/https protocol
	 */
	public static function strip_protocol_from_url( $url ) {
		$pattern = '/^(https?:)?\/\//';
		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Remove index.html/index.php from a URL
	 * @param  string $url URL to remove index file from
	 * @return string      URL sans index file
	 */
	public static function strip_index_filenames_from_url( $url ) {
		$pattern = '/index.(html?|php)$/';
		return preg_replace( $pattern, '', $url );
	}

	/**
	 * Get the current datetime formatted as a string for entry into MySQL
	 * @return string MySQL formatted datetime
	 */
	public static function formatted_datetime() {
		return date( 'Y-m-d H:i:s' );
	}

	/**
	 * Similar to PHP's pathinfo(), but designed with URL paths in mind (instead of directories)
	 *
	 * Example:
	 *   $info = self::url_path_info( '/manual/en/function.pathinfo.php?test=true' );
	 *     $info['dirname']   === '/manual/en/'
	 *     $info['basename']  === 'function.pathinfo.php'
	 *     $info['extension'] === 'php'
	 *     $info['filename']  === 'function.pathinfo'
	 * @param  string $path The URL path
	 * @return array        Array containing info on the parts of the path
	 */
	public static function url_path_info( $path ) {
		$info = array(
			'dirname' => '',
			'basename' => '',
			'filename' => '',
			'extension' => ''
		);

		$path = self::remove_params_and_fragment( $path );

		// everything after the last slash is the filename
		$last_slash_location = strrpos( $path, '/' );
		if ( $last_slash_location === false ) {
			$info['basename'] = $path;
		} else {
			$info['dirname'] = substr( $path, 0, $last_slash_location+1 );
			$info['basename'] = substr( $path, $last_slash_location+1 );
		}

		// finding the dot for the extension
		$last_dot_location = strrpos( $info['basename'], '.' );
		if ( $last_dot_location === false ) {
			$info['filename'] = $info['basename'];
		} else {
			$info['filename'] = substr( $info['basename'], 0, $last_dot_location );
			$info['extension'] = substr( $info['basename'], $last_dot_location+1 );
		}

		// substr sets false if it fails, we're going to reset those values to ''
		foreach ( $info as $name => $value ) {
			if ( $value === false ) {
				$info[ $name ] = '';
			}
		}

		return $info;
	}

	/**
	 * Ensure there is a single trailing directory separator on the path
	 * @param string $path File path to add trailing directory separator to
	 */
	public static function add_trailing_directory_separator( $path ) {
		return self::remove_trailing_directory_separator( $path ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Remove all trailing directory separators
	 * @param string $path File path to remove trailing directory separators from
	 */
	public static function remove_trailing_directory_separator( $path ) {
		return rtrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Ensure there is a single leading directory separator on the path
	 * @param string $path File path to add leading directory separator to
	 */
	public static function add_leading_directory_separator( $path ) {
		return DIRECTORY_SEPARATOR . self::remove_leading_directory_separator( $path );
	}

	/**
	 * Remove all leading directory separators
	 * @param string $path File path to remove leading directory separators from
	 */
	public static function remove_leading_directory_separator( $path ) {
		return ltrim( $path, DIRECTORY_SEPARATOR );
	}

	/**
	 * Add a slash to the beginning of a path
	 * @param string $path URL path to add leading slash to
	 */
	public static function add_leading_slash( $path ) {
		return '/' . self::remove_leading_slash( $path );
	}

	/**
	 * Remove a slash from the beginning of a path
	 * @param string $path URL path to remove leading slash from
	 */
	public static function remove_leading_slash( $path ) {
		return ltrim( $path, '/' );
	}

	/**
	 * Add a message to the array of status messages for the job
	 * @param  array  $messages  Array of messages to add the message to
	 * @param  string $task_name Name of the task
	 * @param  string $message   Message to display about the status of the job
	 * @return void
	 */
	public static function add_archive_status_message( $messages, $task_name, $message ) {
		// if the state exists, set the datetime and message
		if ( ! array_key_exists( $task_name, $messages ) ) {
			$messages[ $task_name ] = array(
				'message' => $message,
				'datetime' => self::formatted_datetime()
			);
		} else { // otherwise just update the message
			$messages[ $task_name ]['message'] = $message;
		}

		return $messages;
	}

}
