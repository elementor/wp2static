<?php

class WPSite {

  public function __construct() {
    $wp_upload_path_and_url = wp_upload_dir();
    $this->uploads_path = $wp_upload_path_and_url['basedir'];
    $this->uploads_url = $wp_upload_path_and_url['baseurl'];
    $this->site_path = ABSPATH;
    $this->site_url = get_site_url() . '/';
    $this->plugin_path = plugins_url('/', __FILE__);
    $this->detect_base_url();
  }

  public function uploadsPathIsWritable() {
    return $this->uploads_path && is_writable($this->uploads_path);
  }

  public function hasCurlSupport() {
    return extension_loaded('curl');
  }

  public function permalinksAreDefined() {
    return strlen(get_option('permalink_structure'));
  }

	public function detect_base_url() {
		$site_url = get_option( 'siteurl' );
		$home = get_option( 'home' );
    $this->subdirectory = '';

		// case for when WP is installed in a different place then being served
		if ( $site_url !== $home ) {
			$this->subdirectory = '/mysubdirectory';
		}

		$base_url = parse_url($site_url);

		if ( array_key_exists('path', $base_url ) && $base_url['path'] != '/' ) {
			$this->subdirectory = $base_url['path'];
		}
	}	

  public function systemRequirementsAreMet() {
    return $this->uploadsPathIsWritable() &&
      $this->hasCurlSupport() &&
      $this->permalinksAreDefined();
  }

  public function getOriginalPaths() {
    $original_directory_names = array();

    $tokens = explode('/', get_template_directory_uri());
    $original_directory_names['theme_dir'] = $tokens[sizeof($tokens)-1];
    $original_directory_names['theme_root'] = $tokens[sizeof($tokens)-2];
    // TODO: use this as a safer way to get wp-content in rewriting areas
    // in case user has changed their wp-content path
    $original_directory_names['wp_contents'] = $tokens[sizeof($tokens)-3];

    $default_upload_dir = wp_upload_dir(); 
    $tokens = explode('/', str_replace(ABSPATH, '/', $default_upload_dir['basedir']));
    $original_directory_names['upload_dir'] = $tokens[sizeof($tokens)-1];


    $tokens = explode('/', WP_PLUGIN_DIR);
    $original_directory_names['plugin_dir'] = $tokens[sizeof($tokens)-1];

    $tokens = explode('/', WPINC);
    $original_directory_names['includes_dir'] = $tokens[sizeof($tokens)-1];

    return $original_directory_names;
  }
}
