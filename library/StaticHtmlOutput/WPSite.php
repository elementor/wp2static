<?php

class WPSite {

  public function __construct() {
    $wp_upload_path_and_url = wp_upload_dir();
    $this->uploadsPath = $wp_upload_path_and_url['basedir'];
    $this->uploadsURL = $wp_upload_path_and_url['baseurl'];
    $this->wp_site_path = ABSPATH;
    $this->wp_site_url = get_site_url();
    $this->wp_plugin_path = plugins_url('/', __FILE__);
    $this->detect_base_url();
  }

  public function uploadsPathIsWritable() {
    return $this->uploadsPath && is_writable($this->uploadsPath);
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
}
