<?php

class StaticHtmlOutput_Controller {
	const VERSION = '5.9';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';

	protected static $_instance = null;
	protected function __construct() {}

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
			self::$_instance->options = new StaticHtmlOutput_Options(self::OPTIONS_KEY);
			self::$_instance->view = new StaticHtmlOutput_View();
		}

		return self::$_instance;
	}

	public static function init($bootstrapFile) {
		$instance = self::getInstance();

		register_activation_hook($bootstrapFile, array($instance, 'activate'));

		if (is_admin()) {
			add_action('admin_menu', array($instance, 'registerOptionsPage'));
      add_filter('custom_menu_order', '__return_true' );
      add_filter('menu_order', array( $instance, 'set_menu_order'));
		}
 
		return $instance;
	}

  public function set_menu_order( $menu_order ) {
    $order = array();
    $file  = plugin_basename( __FILE__ );
    foreach ( $menu_order as $index => $item ) {
        if ( $item == 'index.php') {
            $order[] = $item;
        } 
    }

    $order = array(
      'index.php',
      'wp-static-html-output'
    );

    return $order;
  }

  public function activate_for_single_site() {
      if (null === $this->options->getOption('version')) {
        $this->options
          ->setOption('version', self::VERSION)
          ->setOption('static_export_settings', self::VERSION)
          ->save();
      }
  }

	public function activate($network_wide) {
    if ( $network_wide ) {
      global $wpdb;

      $site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;" );

      foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );
        $this->activate_for_single_site();  
      }

      restore_current_blog();
    } else {
        $this->activate_for_single_site();  
    } 
	}

	public function registerOptionsPage() {
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		$page = add_menu_page(
			__('WP Static Site Generator', 'static-html-output-plugin'), 
			__('WP Static Site Generator', 'static-html-output-plugin'), 
			'manage_options', 
			self::HOOK, 
			array(self::$_instance, 'renderOptionsPage'),
			$pluginDirUrl . 'images/menu_icon_32x32.png'
		);

		add_action('admin_print_styles-' . $page, array($this, 'enqueueAdminStyles'));
	}

	public function enqueueAdminStyles() {
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		wp_enqueue_style(self::HOOK . '-admin', $pluginDirUrl . '/css/wp-static-html-output.css');
	}

  public function generate_filelist_preview() {
    // TODO: get independent from WP calls
    $uploads_path = isset($_POST['wp_uploads_path']) ? $_POST['wp_uploads_path'] : '';
    $uploads_url = isset($_POST['wp_uploads_url']) ? $_POST['wp_uploads_url'] : '';
    $working_directory = isset($_POST['working_directory']) ? $_POST['working_directory'] : '';
    $plugin_hook = 'wp-static-html-output';

    // pre-generated the initial crawl list
    $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildInitialFileList(
      true,
      $uploads_path,
      $uploads_url,
      $working_directory,
      $plugin_hook
    );

    echo $initial_file_list_count;
  }

	public function renderOptionsPage() {

    require_once dirname(__FILE__) . '/StaticHtmlOutput/WPSite.php';

    $this->wp_site = new WPSite();

    $this->current_archive = '';

      // load settings via Client or from DB if run from CLI
      if (null !== (filter_input(INPUT_POST, 'selected_deployment_option'))) {
        self::$_instance->selected_deployment_option = filter_input(INPUT_POST, 'selected_deployment_option');
        self::$_instance->baseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));
        self::$_instance->diffBasedDeploys = filter_input(INPUT_POST, 'diffBasedDeploys');
      } 

		if (! $this->wp_site->systemRequirementsAreMet()) {
			$this->view
				->setTemplate('system-requirements')
				->assign('wp_site', $this->wp_site)
				->render();
		} else {
			$this->view
				->setTemplate('options-page-js')
				->assign('working_directory', $this->getWorkingDirectory())
				->assign('options', $this->options)
				->assign('wp_site', $this->wp_site)
				->assign('onceAction', self::HOOK . '-options')
				->render();

			$this->view
				->setTemplate('options-page')
				->assign('wp_site', $this->wp_site)
				->assign('options', $this->options)
				->assign('onceAction', self::HOOK . '-options')
				->render();
		}
	}

  public function save_options () {
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
			exit('You cannot change WP Static Site Generator Plugin options.');
		}

		$this->options->saveAllPostData();
  }

	public function getWorkingDirectory(){
		$outputDir = '';

		// priorities: from UI; from settings; fallback to WP uploads path
		if (isset($this->workingDirectory) ) {
			$outputDir = $this->workingDirectory;
		} elseif ($this->options->oworkingDirectory) {
      $outputDir = $this->options->workingDirectory;
    } else {
      $outputDir = $this->wp_site->uploads_path;
    }

		if ( ! is_dir($outputDir) && ! wp_mkdir_p($outputDir)) {
      $outputDir = $this->wp_site->uploads_path;
      error_log('user defined outputPath does not exist and could not be created, reverting to ' . $outputDir);
    } 

		if ( empty($outputDir) || !is_writable($outputDir) ) {
			$outputDir = $this->wp_site->uploads_path;
      error_log('user defined outputPath is not writable, reverting to ' . $outputDir);
		}

		return $outputDir;
	}

	public function prepare_for_export($viaCLI = false) {
    require_once dirname(__FILE__) . '/StaticHtmlOutput/Exporter.php';
    $exporter = new Exporter();

    $exporter->capture_last_deployment();
		$exporter->pre_export_cleanup();
    $exporter->cleanup_leftover_archives();
    $exporter->initialize_cache_files();

    // TODO: move to exporter; wp env vars to views
    WsLog::l('STARTING EXPORT ' . date("Y-m-d h:i:s") );
    WsLog::l('STARTING EXPORT: PHP VERSION ' . phpversion() );
    WsLog::l('STARTING EXPORT: PHP MAX EXECUTION TIME ' . ini_get('max_execution_time') );
    WsLog::l('STARTING EXPORT: OS VERSION ' . php_uname() );
    WsLog::l('STARTING EXPORT: WP VERSION ' . get_bloginfo('version') );
    WsLog::l('STARTING EXPORT: WP URL ' . get_bloginfo('url') );
    WsLog::l('STARTING EXPORT: WP SITEURL ' . get_option('siteurl') );
    WsLog::l('STARTING EXPORT: WP HOME ' . get_option('home') );
    WsLog::l('STARTING EXPORT: WP ADDRESS ' . get_bloginfo('wpurl') );
    WsLog::l('STARTING EXPORT: PLUGIN VERSION ' . $this::VERSION );
    WsLog::l('STARTING EXPORT: VIA CLI? ' . $viaCLI);
    WsLog::l('STARTING EXPORT: STATIC EXPORT URL ' . $exporter->baseUrl);

    $exporter->generateModifiedFileList();

    echo 'SUCCESS';
  }

  public function deploy() {
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/Deployer.php';
    $deployer = new Deployer();
    $deployer->deploy();
  }

  public function doExportWithoutGUI() {
    if ( wpsho_fr()->is_plan('professional_edition') ) {
  
      //$this->capture_last_deployment(); 
      $this->cleanup_leftover_archives(true);
      $this->start_export(true);
      $this->crawl_site(true);
      $this->post_process_archive_dir(true);
      $this->deploy();
      $this->post_export_teardown();

      //$this->create_zip();
    }
  }

	public function reset_default_settings() {
    if (! delete_option('wp-static-html-output-options')) {
      error_log("Couldn't reset plugin to default settings");
    }

		echo 'SUCCESS';
	}	

  public function post_process_archive_dir() {
      // TODO: bypass plugin instantiating to process
      require_once dirname(__FILE__) . '/StaticHtmlOutput/ArchiveProcessor.php';
      $processor = new ArchiveProcessor();

      $processor->create_symlink_to_latest_archive();

      $processor->renameWPDirectories();
	}
}
