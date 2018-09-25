<?php
/**
 * StaticHtmlOutput_Controller
 *
 * @package WP2Static
 */
class StaticHtmlOutput_Controller {

    const VERSION = '5.9';
    const OPTIONS_KEY = 'wp-static-html-output-options';
    const HOOK = 'wp-static-html-output';

    protected static $_instance = null;


    /**
     * Singleton protected constructor
     */
    protected function __construct() {}


    /**
     * Get Singleton instance
     *
     * @return object
     */
    public static function getInstance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
            self::$_instance->options = new StaticHtmlOutput_Options(
                self::OPTIONS_KEY
            );
            self::$_instance->view = new StaticHtmlOutput_View();
        }

        return self::$_instance;
    }


    /**
     * Initialize controller
     *
     * @param string $bootstrapFile Bootstrap file
     * @return object
     */
    public static function init( $bootstrapFile ) {
        $instance = self::getInstance();

        register_activation_hook(
            $bootstrapFile,
            array( $instance, 'activate' )
        );

        if ( is_admin() ) {
            add_action(
                'admin_menu',
                array( $instance, 'registerOptionsPage' )
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', array( $instance, 'set_menu_order' ) );
        }

        return $instance;
    }


    /**
     * Set menu order
     *
     * @param array $menu_order Menu order
     * @return array
     */
    public function set_menu_order( $menu_order ) {
        $order = array();
        $file  = plugin_basename( __FILE__ );
        foreach ( $menu_order as $index => $item ) {
            if ( $item == 'index.php' ) {
                $order[] = $item;
            }
        }

        $order = array(
            'index.php',
            'wp-static-html-output',
        );

        return $order;
    }


    /**
     * Single site activation
     *
     * @return void
     */
    public function activate_for_single_site() {
        if ( null === $this->options->getOption( 'version' ) ) {
            $this->options
                ->setOption( 'version', self::VERSION )
                ->setOption( 'static_export_settings', self::VERSION )
                ->save();
        }
    }


    /**
     * Activate plugin
     *
     * @param boolean $network_wide Network-wide flag
     * @return void
     */
    public function activate( $network_wide ) {
        if ( $network_wide ) {
            global $wpdb;

            $query_ids = "
                SELECT
                    blog_id
                FROM
                    $wpdb->blogs
                WHERE
                    site_id = $wpdb->siteid;
                ";
            $site_ids = $wpdb->get_col( $query_ids );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                $this->activate_for_single_site();
            }

            restore_current_blog();
        } else {
            $this->activate_for_single_site();
        }//end if
    }


    /**
     * Register options page
     *
     * @return void
     */
    public function registerOptionsPage() {
        $pluginDirUrl = plugin_dir_url( dirname( __FILE__ ) );
        $page = add_menu_page(
            __( 'WP Static Site Generator', 'static-html-output-plugin' ),
            __( 'WP Static Site Generator', 'static-html-output-plugin' ),
            'manage_options',
            self::HOOK,
            array( self::$_instance, 'renderOptionsPage' ),
            $pluginDirUrl . 'images/menu_icon_32x32.png'
        );

        add_action(
            'admin_print_styles-' . $page,
            array( $this, 'enqueueAdminStyles' )
        );
    }


    /**
     * Enqueue admin styles
     *
     * @return void
     */
    public function enqueueAdminStyles() {
        $pathCSS = plugin_dir_url( dirname( __FILE__ ) ) .
            '/css/wp-static-html-output.css';
        wp_enqueue_style(
            self::HOOK . '-admin',
            $pathCSS,
            array(),
            static::VERSION
        );
    }


    /**
     * Generate preview of file list
     *
     * @return void
     */
    public function generate_filelist_preview() {
        // pre-generated the initial crawl list
        $classHelper = 'StaticHtmlOutput_FilesHelper';
        $initial_file_list_count = $classHelper::buildInitialFileList(
            true,
            /**
             * simulate viaCLI for debugging, will only be called via UI, but
             * without response needed
             *
             * $this->getWorkingDirectory(),
             */
            /**
             * NOTE: Working Dir not yet available, so we serve generate list
             * NOTE: ... under uploads dir
             */
            $this->uploadsPath,
            $this->uploadsURL,
            $this->getWorkingDirectory(),
            self::HOOK
        );

        echo $initial_file_list_count;
    }


    /**
     * Render options page
     *
     * @return void
     */
    public function renderOptionsPage() {

        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/WPSite.php';

        $this->wp_site = new WPSite();

        $this->current_archive = '';

        // load settings via Client or from DB if run from CLI
        $optionDeployment = filter_input(
            INPUT_POST,
            'selected_deployment_option'
        );
        if ( null !== $optionDeployment ) {
            self::$_instance->selected_deployment_option = filter_input(
                INPUT_POST,
                'selected_deployment_option'
            );
            self::$_instance->baseUrl = untrailingslashit(
                filter_input( INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL )
            );
            self::$_instance->diffBasedDeploys = filter_input(
                INPUT_POST,
                'diffBasedDeploys'
            );
        }

        // supply views with wp_install_subdir if present
        $this->detect_base_url();

        if (
            ! $uploadsFolderWritable ||
            ! $permalinksStructureDefined ||
            ! $supports_cURL
        ) {
            $this->view
                ->setTemplate( 'system-requirements' )
                ->assign( 'wp_site', $this->wp_site )
                ->render();
        } else {
            $this->view
                ->setTemplate( 'options-page-js' )
                ->assign( 'working_directory', $this->getWorkingDirectory() )
                ->assign( 'options', $this->options )
                ->assign( 'wp_site', $this->wp_site )
                ->assign( 'onceAction', self::HOOK . '-options' )
                ->render();

            $this->view
                ->setTemplate( 'options-page' )
                ->assign( 'wp_site', $this->wp_site )
                ->assign( 'options', $this->options )
                ->assign( 'onceAction', self::HOOK . '-options' )
                ->render();
        }//end if
    }


    /**
     * Save options
     *
     * @return void
     */
    public function save_options() {
        if (
            ! check_admin_referer( self::HOOK . '-options' ) ||
            ! current_user_can( 'manage_options' )
        ) {
            exit(
                'You cannot change WP Static Site Generator Plugin options.'
            );
        }

        $this->options->saveAllPostData();
    }


    /**
     * Get working directory
     *
     * @return string
     */
    public function getWorkingDirectory() {
        $outputDir = '';

        // priorities: from UI; from settings; fallback to WP uploads path
        if ( isset( $this->outputDirectory ) ) {
            $outputDir = $this->outputDirectory;
        } elseif ( $this->options->outputDirectory ) {
            $outputDir = $this->options->outputDirectory;
        } else {
            $outputDir = $this->uploadsPath;
        }

        if ( ! is_dir( $outputDir ) && ! wp_mkdir_p( $outputDir ) ) {
            $outputDir = $this->uploadsPath;
            error_log(
                'user defined outputPath does not exist and could not ' .
                ' be created, reverting to ' . $outputDir
            );
        }

        if ( empty( $outputDir ) || ! is_writable( $outputDir ) ) {
            $outputDir = $this->uploadsPath;
            error_log(
                'user defined outputPath is not writable, reverting to ' .
                $outputDir
            );
        }

        return $outputDir;
    }


    /**
     * Prepare for export
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function prepare_for_export( $viaCLI = false ) {
        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/Exporter.php';
        $exporter = new Exporter();

        $exporter->capture_last_deployment();
        $exporter->pre_export_cleanup();
        $exporter->cleanup_leftover_archives();
        $exporter->initialize_cache_files();

        // TODO: move to exporter; wp env vars to views
        WsLog::l( 'STARTING EXPORT ' . date( 'Y-m-d h:i:s' ) );
        WsLog::l( 'STARTING EXPORT: PHP VERSION ' . phpversion() );
        WsLog::l(
            'STARTING EXPORT: PHP MAX EXECUTION TIME ' .
            ini_get( 'max_execution_time' )
        );
        WsLog::l( 'STARTING EXPORT: OS VERSION ' . php_uname() );
        WsLog::l( 'STARTING EXPORT: WP VERSION ' . get_bloginfo( 'version' ) );
        WsLog::l( 'STARTING EXPORT: WP URL ' . get_bloginfo( 'url' ) );
        WsLog::l( 'STARTING EXPORT: WP SITEURL ' . get_option( 'siteurl' ) );
        WsLog::l( 'STARTING EXPORT: WP HOME ' . get_option( 'home' ) );
        WsLog::l( 'STARTING EXPORT: WP ADDRESS ' . get_bloginfo( 'wpurl' ) );
        WsLog::l( 'STARTING EXPORT: PLUGIN VERSION ' . $this::VERSION );
        WsLog::l( 'STARTING EXPORT: VIA CLI? ' . $viaCLI );
        WsLog::l( 'STARTING EXPORT: STATIC EXPORT URL ' . $this->baseUrl );

        $exporter->generateModifiedFileList();

        echo 'SUCCESS';
    }


    /**
     * Deploy
     *
     * @return void
     */
    public function deploy() {
        require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/Deployer.php';
        $deployer = new Deployer();
        $deployer->deploy();
    }


    /**
     * Export w/o GUI
     *
     * @return void
     */
    public function doExportWithoutGUI() {
        if ( wpsho_fr()->is_plan( 'professional_edition' ) ) {

            // $this->capture_last_deployment();
            $this->cleanup_leftover_archives( true );
            $this->start_export( true );
            $this->crawl_site( true );
            $this->post_process_archive_dir( true );
            $this->deploy();
            $this->post_export_teardown();

            // $this->create_zip();
        }
    }


    /**
     * Reset to default settings
     *
     * @return void
     */
    public function reset_default_settings() {
        $this->options
            ->setOption( 'static-export-settings', '' )
            ->save();

        echo 'SUCCESS';
    }


    /**
     * Post-processing for archive directory
     *
     * @return void
     */
    public function post_process_archive_dir() {
        // TODO: bypass plugin instantiating to process
        require_once dirname( __FILE__ ) .
            '/StaticHtmlOutput/ArchiveProcessor.php';
        $processor = new ArchiveProcessor();

        $processor->create_symlink_to_latest_archive();
    }

}
