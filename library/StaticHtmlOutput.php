<?php

class StaticHtmlOutput_Controller {
    const VERSION = '6.3';
    const OPTIONS_KEY = 'wp2static-options';
    const HOOK = 'wp2static';

    protected static $instance = null;

    protected function __construct() {}

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->options = new StaticHtmlOutput_Options(
                self::OPTIONS_KEY
            );
            self::$instance->view = new StaticHtmlOutput_View();
        }

        return self::$instance;
    }

    public static function init( $bootstrap_file ) {
        $instance = self::getInstance();

        register_activation_hook(
            $bootstrap_file,
            array( $instance, 'activate' )
        );

        if ( is_admin() ) {
            add_action(
                'admin_menu',
                array(
                    $instance,
                    'registerOptionsPage',
                )
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', array( $instance, 'set_menu_order' ) );
        }
        return $instance;
    }


    public function set_menu_order( $menu_order ) {
        $order = array();
        $file  = plugin_basename( __FILE__ );
        foreach ( $menu_order as $index => $item ) {
            if ( $item === 'index.php' ) {
                $order[] = $item;
            }
        }

        $order = array(
            'index.php',
            'wp2static',
        );

        return $order;
    }


    public function setDefaultOptions() {
        if ( null === $this->options->getOption( 'version' ) ) {
            $this->options
            ->setOption( 'version', self::VERSION )
            ->setOption( 'static_export_settings', self::VERSION )
            // set default options
            ->setOption( 'rewriteWPPaths', '1' )
            ->setOption( 'removeConditionalHeadComments', '1' )
            ->setOption( 'removeWPMeta', '1' )
            ->setOption( 'removeWPLinks', '1' )
            ->setOption( 'removeHTMLComments', '1' )
            ->save();
        }
    }

    public function activate_for_single_site() {
        $this->setDefaultOptions();
    }

    public function activate( $network_wide ) {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

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
        $plugins_url = plugin_dir_url( dirname( __FILE__ ) );
        $page = add_menu_page(
            __( 'WP2Static', 'static-html-output-plugin' ),
            __( 'WP2Static', 'static-html-output-plugin' ),
            'manage_options',
            self::HOOK,
            array( self::$instance, 'renderOptionsPage' ),
            'dashicons-shield-alt'
        );

        add_action(
            'admin_print_styles-' . $page,
            array(
                $this,
                'enqueueAdminStyles',
            )
        );
    }

    public function enqueueAdminStyles() {
        $plugins_url = plugin_dir_url( dirname( __FILE__ ) );

        wp_enqueue_style(
            self::HOOK . '-admin',
            $plugins_url . 'wp2static.css?sdf=sdfd',
            null,
            $this::VERSION
        );
    }

    public function finalize_deployment() {
        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/Deployer.php';

        $deployer = new Deployer();
        $deployer->finalizeDeployment();

        echo 'SUCCESS';
    }

    public function generate_filelist_preview() {
        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/WPSite.php';
        $this->wp_site = new WPSite();

        $target_settings = array(
            'general',
            'crawling',
        );

        if ( defined( 'WP_CLI' ) ) {
            require_once dirname( __FILE__ ) .
                '/StaticHtmlOutput/DBSettings.php';

            $this->settings =
                WPSHO_DBSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/StaticHtmlOutput/PostSettings.php';

            $this->settings =
                WPSHO_PostSettings::get( $target_settings );
        }

        $plugin_hook = 'wp2static';

        $initial_file_list_count =
            StaticHtmlOutput_FilesHelper::buildInitialFileList(
                true,
                $this->wp_site->wp_uploads_path,
                $this->wp_site->uploads_url,
                $this->settings
            );

        if ( ! defined( 'WP_CLI' ) ) {
            echo $initial_file_list_count;
        }
    }

    public function renderOptionsPage() {
        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/WPSite.php';

        $this->wp_site = new WPSite();
        $this->current_archive = '';

        $this->view
            ->setTemplate( 'options-page-js' )
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
    }

    public function userIsAllowed() {
        $referred_by_admin = check_admin_referer( self::HOOK . '-options' );
        $user_can_manage_options = current_user_can( 'manage_options' );

        return $referred_by_admin && $user_can_manage_options;
    }

    public function save_options() {
        if ( ! $this->userIsAllowed() ) {
            exit( 'Not allowed to change plugin options.' );
        }

        $this->options->saveAllPostData();
    }

    public function prepare_for_export() {
        require_once dirname( __FILE__ ) .
            '/StaticHtmlOutput/Exporter.php';

        $exporter = new Exporter();

        $exporter->pre_export_cleanup();
        $exporter->cleanup_leftover_archives();
        $exporter->initialize_cache_files();

        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/Archive.php';

        $archive = new Archive();
        $archive->create();

        $via_cli = defined( 'WP_CLI' );

        WsLog::l( '' . date( 'Y-m-d h:i:s' ) );
        WsLog::l( 'PHP VERSION ' . phpversion() );
        WsLog::l( 'OS VERSION ' . php_uname() );
        WsLog::l( 'WP VERSION ' . get_bloginfo( 'version' ) );
        WsLog::l( 'WP URL ' . get_bloginfo( 'url' ) );
        WsLog::l( 'WP SITEURL ' . get_option( 'siteurl' ) );
        WsLog::l( 'WP HOME ' . get_option( 'home' ) );
        WsLog::l( 'WP ADDRESS ' . get_bloginfo( 'wpurl' ) );
        WsLog::l( 'PLUGIN VERSION ' . $this::VERSION );
        WsLog::l( 'VIA WP-CLI? ' . $via_cli );
        WsLog::l(
            'STATIC EXPORT URL ' .
            $exporter->settings['baseUrl']
        );

        $exporter->generateModifiedFileList();

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function reset_default_settings() {
        if ( ! delete_option( 'wp2static-options' ) ) {
            error_log( "Couldn't reset plugin to default settings" );
        }

        $this->options = new StaticHtmlOutput_Options( self::OPTIONS_KEY );
        $this->setDefaultOptions();

        echo 'SUCCESS';
    }

    public function post_process_archive_dir() {
        require_once dirname( __FILE__ ) .
            '/StaticHtmlOutput/ArchiveProcessor.php';
        $processor = new ArchiveProcessor();

        $processor->createNetlifySpecialFiles();
        // NOTE: renameWP Directories also doing same server publish
        $processor->renameArchiveDirectories();
        $processor->removeWPCruft();
        $processor->copyStaticSiteToPublicFolder();
        $processor->create_zip();

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }

    public function delete_deploy_cache() {
        $target_settings = array(
            'wpenv',
        );

        if ( defined( 'WP_CLI' ) ) {
            require_once dirname( __FILE__ ) .
                '/StaticHtmlOutput/DBSettings.php';

            $this->settings =
                WPSHO_DBSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/StaticHtmlOutput/PostSettings.php';

            $this->settings =
                WPSHO_PostSettings::get( $target_settings );
        }
        $uploads_dir = $this->settings['wp_uploads_path'];

        $cache_files = array(
            '/WP2STATIC-GITLAB-PREVIOUS-HASHES.txt',
            '/WP2STATIC-GITHUB-PREVIOUS-HASHES.txt',
            '/WP2STATIC-S3-PREVIOUS-HASHES.txt',
            '/WP2STATIC-BUNNYCDN-PREVIOUS-HASHES.txt',
            '/WP2STATIC-BITBUCKET-PREVIOUS-HASHES.txt',
            '/WP2STATIC-FTP-PREVIOUS-HASHES.txt',
        );

        foreach ( $cache_files as $cache_file ) {
            if ( is_file( $uploads_dir . $cache_file ) ) {
                unlink( $uploads_dir . $cache_file );
            }
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }
}
