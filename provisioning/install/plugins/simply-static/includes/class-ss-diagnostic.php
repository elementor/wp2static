<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static Diagnostic class
 *
 * Checks to ensure that the user's server and WP installation meet a set of
 * minimum requirements.
 */
class Diagnostic {

	/** @const */
	protected static $min_version = array(
		'php' => '5.3.0',
		'curl' => '7.15.0'
	);

	/**
	 * Assoc. array of categories, and then functions to check
	 * @var array
	 */
	protected $description = array(
		'URLs' => array(),
		'Filesystem' => array(
			array( 'function' => 'is_temp_files_dir_readable' ),
			array( 'function' => 'is_temp_files_dir_writeable' )
		),
		'WordPress' => array(
			array( 'function' => 'is_permalink_structure_set' ),
			array( 'function' => 'can_wp_make_requests_to_itself' )
		),
		'MySQL' => array(
			array( 'function' => 'user_can_delete' ),
			array( 'function' => 'user_can_insert' ),
			array( 'function' => 'user_can_select' ),
			array( 'function' => 'user_can_create' ),
			array( 'function' => 'user_can_alter' ),
			array( 'function' => 'user_can_drop' )
		),
		'PHP' => array(
			array( 'function' => 'php_version' ),
			array( 'function' => 'has_curl' )
		)
	);

	/**
	 * Assoc. array of results of the diagnostic check
	 * @var array
	 */
	public $results = array();

	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var Simply_Static\Options
	 */
	protected $options = null;

	public function __construct() {
		$this->options = Options::instance();

		if ( $this->options->get( 'destination_url_type' ) == 'absolute' ) {
			$this->description['URLs'][] = array(
				'function' => 'is_destination_host_a_valid_url'
			);
		}

		if ( $this->options->get( 'delivery_method' ) == 'local' ) {
			$this->description['Filesystem'][] = array(
				'function' => 'is_local_dir_writeable'
			);
		}

		$additional_urls = Util::string_to_array( $this->options->get( 'additional_urls' ) );
		foreach ( $additional_urls as $url ) {
			$this->description['URLs'][] = array(
				'function' => 'is_additional_url_valid',
				'param' => $url
			);
		}

		$additional_files = Util::string_to_array( $this->options->get( 'additional_files' ) );
		foreach ( $additional_files as $file ) {
			$this->description['Filesystem'][] = array(
				'function' => 'is_additional_file_valid',
				'param' => $file
			);
		}

		foreach ( $this->description as $title => $tests ) {
			$this->results[ $title ] = array();
			foreach ( $tests as $test ) {
				$param = isset( $test['param'] ) ? $test['param'] : null;
				$result = $this->{$test['function']}( $param );

				if ( ! isset( $result['message'] ) ) {
					$result['message'] = $result['test'] ? __( 'OK', 'simply-static' ) : __( 'FAIL', 'simply-static' );
				}

				$this->results[ $title ][] = $result;
			}
		}
	}

	public function is_destination_host_a_valid_url() {
		$destination_scheme = $this->options->get( 'destination_scheme' );
		$destination_host = $this->options->get( 'destination_host' );
		$destination_url = $destination_scheme . $destination_host;
		$label = sprintf( __( 'Checking if Destination URL <code>%s</code> is valid', 'simply-static' ), $destination_url );
		return array(
			'label' => $label,
			'test' => filter_var( $destination_url, FILTER_VALIDATE_URL ) !== false
		);
	}

	public function is_additional_url_valid( $url ) {
		$label = sprintf( __( 'Checking if Additional URL <code>%s</code> is valid', 'simply-static' ), $url );
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			$test = false;
			$message = __( 'Not a valid URL', 'simply-static' );
		} else if ( ! Util::is_local_url( $url ) ) {
			$test = false;
			$message = __( 'Not a local URL', 'simply-static' );
		} else {
			$test = true;
			$message = null;
		}

		return array(
			'label' => $label,
			'test' => $test,
			'message' => $message
		);
	}

	public function is_additional_file_valid( $file ) {
		$label = sprintf( __( 'Checking if Additional File/Dir <code>%s</code> is valid', 'simply-static' ), $file );
		if ( stripos( $file, get_home_path() ) !== 0 && stripos( $file, WP_PLUGIN_DIR ) !== 0 && stripos( $file, WP_CONTENT_DIR ) !== 0 ) {
			$test = false;
			$message = __( 'Not a valid path', 'simply-static' );
		} else if ( ! is_readable( $file ) ) {
			$test = false;
			$message = __( 'Not readable', 'simply-static' );;
		} else {
			$test = true;
			$message = null;
		}

		return array(
			'label' => $label,
			'test' => $test,
			'message' => $message
		);
	}

	public function is_permalink_structure_set() {
		$label = __( 'Checking if WordPress permalink structure is set', 'simply-static' );
		return array(
			'label' => $label,
			'test' => strlen( get_option( 'permalink_structure' ) ) !== 0
		);
	}

	public function can_wp_make_requests_to_itself() {
		$ip_address = getHostByName( getHostName() );
		$label = sprintf( __( "Checking if WordPress can make requests to itself from <code>%s</code>", 'simply-static' ), $ip_address );

		$url = Util::origin_url();
		$response = Url_Fetcher::remote_get( $url );

		if ( is_wp_error( $response ) ) {
			$test = false;
			$message = null;
		} else {
			$code = $response['response']['code'];
			if ( $code == 200 ) {
				$test = true;
				$message = $code;
			} else if ( in_array( $code, Page::$processable_status_codes ) ) {
				$test = false;
				$message = sprintf( __( "Received a %s response. This might indicate a problem.", 'simply-static' ), $code );
			} else {
				$test = false;
				$message = sprintf( __( "Received a %s response.", 'simply-static' ), $code );;
			}
		}

		return array(
			'label' => $label,
			'test' => $test,
			'message' => $message
		);
	}

	public function is_temp_files_dir_readable() {
		$temp_files_dir = $this->options->get( 'temp_files_dir' );
		$label = sprintf( __( "Checking if web server can read from Temp Files Directory: <code>%s</code>", 'simply-static' ), $temp_files_dir );
		return array(
			'label' => $label,
			'test' => is_readable( $temp_files_dir )
		);
	}

	public function is_temp_files_dir_writeable() {
		$temp_files_dir = $this->options->get( 'temp_files_dir' );
		$label = sprintf( __( "Checking if web server can write to Temp Files Directory: <code>%s</code>", 'simply-static' ), $temp_files_dir );
		return array(
			'label' => $label,
			'test' => is_writable( $temp_files_dir )
		);
	}

	public function is_local_dir_writeable() {
		$local_dir = $this->options->get( 'local_dir' );
		$label = sprintf( __( "Checking if web server can write to Local Directory: <code>%s</code>", 'simply-static' ), $local_dir );
		return array(
			'label' => $label,
			'test' => is_writable( $local_dir )
		);
	}

	public function user_can_delete() {
		$label = __( 'Checking if MySQL user has <code>DELETE</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'delete' )
		);
	}

	public function user_can_insert() {
		$label = __( 'Checking if MySQL user has <code>INSERT</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'insert' )
		);
	}

	public function user_can_select() {
		$label = __( 'Checking if MySQL user has <code>SELECT</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'select' )
		);
	}

	public function user_can_create() {
		$label = __( 'Checking if MySQL user has <code>CREATE</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'create' )
		);
	}

	public function user_can_alter() {
		$label = __( 'Checking if MySQL user has <code>ALTER</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'alter' )
		);
	}

	public function user_can_drop() {
		$label = __( 'Checking if MySQL user has <code>DROP</code> privilege', 'simply-static' );
		return array(
			'label' => $label,
			'test' => Sql_Permissions::instance()->can( 'drop' )
		);
	}

	public function php_version() {
		$label = sprintf( __( 'Checking if PHP version >= %s', 'simply-static' ), self::$min_version['php'] );
		return array(
			'label' => $label,
			'test' => version_compare( phpversion(), self::$min_version['php'], '>=' ),
			'message'  => phpversion(),
		);
	}

	public function has_curl() {
		$label = __( 'Checking for cURL support', 'simply-static' );

		if ( is_callable( 'curl_version' ) ) {
			$version = curl_version();
			$test = version_compare( $version['version'], self::$min_version['curl'], '>=' );
			$message = $version['version'];
		} else {
			$test = false;
			$message = null;
		}

		return array(
			'label' => $label,
			'test' => $test,
			'message'  => $message,
		);
	}

}
