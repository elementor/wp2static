<?php
/**
 * StaticHtmlOutput_View
 *
 * @package WP2Static
 */
class StaticHtmlOutput_View {

    protected $_variables = array();
    protected $_path = null;
    protected $_directory = 'views';
    protected $_extension = '.phtml';
    protected $_template = null;


    /**
     * Constructor
     */
    public function __construct() {
        // Looking for a basic directory where plugin resides
        list($pluginDir) = explode( '/', plugin_basename( __FILE__ ) );

        // making up an absolute path to views directory
        $pathArray = array( WP_PLUGIN_DIR, $pluginDir, $this->_directory );

        $this->_path = implode( '/', $pathArray );
    }


    /**
     * Set template
     *
     * @param string $template Template
     * @return object
     */
    public function setTemplate( $template ) {
        $this->_template = $template;
        $this->_variables = array();
        return $this;
    }


    /**
     * Setter magic method
     *
     * @param string $name  Member variable name
     * @param mixed  $value Value to set
     * @return object
     */
    public function __set( $name, $value ) {
        $this->_variables[ $name ] = $value;
        return $this;
    }


    /**
     * Assign (set)
     *
     * @param string $name  Member variable name
     * @param mixed  $value Value to set
     * @return object
     */
    public function assign( $name, $value ) {
        return $this->__set( $name, $value );
    }


    /**
     * Getter magic method
     *
     * @param string $name Member variable name
     * @return mixed
     */
    public function __get( $name ) {
        $value = array_key_exists( $name, $this->_variables )
            ? $this->_variables[ $name ]
            : null;
        return $value;
    }


    /**
     * Render view
     *
     * @return object
     */
    public function render() {
        $file = $this->_path . '/' . $this->_template . $this->_extension;

        if ( ! is_readable( $file ) ) {
            error_log( 'Can\'t find view template: ' . $file );
        }

        include $file;

        return $this;
    }


    /**
     * Fetch view
     *
     * @return string
     */
    public function fetch() {
        ob_start();

        $this->render();
        $contents = ob_get_contents();

        ob_end_clean();

        return $contents;
    }
}

