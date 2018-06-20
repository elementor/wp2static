<?php
namespace Simply_Static;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simply Static view class
 */
class View {

	/**
	 * Base directory for views
	 * @var string
	 */
	const DIRECTORY = 'views';

	/**
	 * View script extension
	 * @var string
	 */
	const EXTENSION = '.php';

	/**
	 * View variables array
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Absolute path for view
	 * @var string
	 */
	protected $path = null;

	/**
	 * Template file name to render
	 * @var string
	 */
	protected $template = null;

	/**
	 * Flashes are quick status messages displayed at the top of the page
	 * @var array
	 */
	protected $flashes = array();

	/**
	 * Contructor - Performs initialization of the absolute path for views
	 */
	public function __construct() {
		// Looking for a basic directory where plugin resides
		list($plugin_dir) = explode( '/', plugin_basename( __FILE__ ) );

		// create an absolute path to views directory
		$path_array = array( WP_PLUGIN_DIR, $plugin_dir, self::DIRECTORY );

		$this->path = implode( '/', $path_array );
	}

	/**
	 * Sets a layout that will be used later in render() method
	 * @param string $template The template filename, without extension
	 * @return Simply_Static\View
	 */
	public function set_layout( $layout ) {
		$this->layout = trailingslashit( $this->path ) . 'layouts/' . $layout . self::EXTENSION;

		return $this;
	}

	/**
	 * Sets a template that will be used later in render() method
	 * @param string $template The template filename, without extension
	 * @return Simply_Static\View
	 */
	public function set_template( $template ) {
		$this->template = trailingslashit( $this->path ) . $template . self::EXTENSION;

		return $this;
	}

	/**
	 * Returns a value of the option identified by $name
	 * @param string $name The option name
	 * @return mixed|null
	 */
	public function __get( $name ) {
		$value = array_key_exists( $name, $this->variables ) ? $this->variables[ $name ] : null;
		return $value;
	}

	/**
	* Updates the view variable identified by $name with the value provided in $value
	* @param string $name The variable name
	* @param mixed  $value The variable value
	* @return Simply_Static\View
	*/
	public function __set( $name, $value ) {
		$this->variables[ $name ] = $value;
		return $this;
	}

	/**
	 * Updates the view variable identified by $name with the value provided in $value
	 * @param string $name The variable name
	 * @param mixed $value The variable value
	 * @return Simply_Static\View
	 */
	public function assign( $name, $value ) {
		return $this->__set( $name, $value );
	}

	/**
	 * Add a flash message to be displayed at the top of the page
	 *
	 * Available types: 'updated' (green), 'error' (red), 'notice' (no color)
	 *
	 * @param string $type The type of message to be displayed
	 * @param string $message The message to be displayed
	 * @return void
	 */
	public function add_flash( $type, $message ) {
		array_push( $this->flashes, array( 'type' => $type, 'message' => $message ) );
	}

	/**
	 * Returns the layout (if available) or template
	 *
	 * Checks to make sure that the file exists and is readable.
	 *
	 * @return string|\WP_Error
	 */
	private function get_renderable_file() {

		// must include a template
		if ( ! is_readable( $this->template ) ) {
			return new \WP_Error( 'invalid_template', sprintf( __( "Can't find view template: %s", 'simply-static' ), $this->template ) );
		}

		// layouts are optional. if no layout provided, use the template by itself.
		if ( $this->layout ) {
			if ( ! is_readable( $this->layout ) ) {
				return new \WP_Error( 'invalid_layout', sprintf( __( "Can't find view layout: %s", 'simply-static' ), $this->layout ) );
			} else {
				// the layout should include the template
				return $this->layout;
			}
		} else {
			return $this->template;
		}

	}

	/**
	 * Renders the view script.
	 *
	 * @return Simply_Static\View|\WP_Error
	 */
	public function render() {

		$file = $this->get_renderable_file();

		if ( is_wp_error( $file ) ) {
			return $file;
		} else {
			include $file;
			return $this;
		}

	}

	/**
	 * Returns the view as a string.
	 *
	 * @return string|\WP_Error
	 */
	public function render_to_string() {

		$file = $this->get_renderable_file();

		if ( is_wp_error( $file ) ) {
			return $file;
		} else {
			ob_start();
		include $file;
		return ob_get_clean();
		}

	}
}
