<?php


class Deployer {

    public function __construct() {
        $target_settings = array(
            'general',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';
            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/DBSettings.php';
            
            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

    }

    public function deploy( $test = false ) {
        $powerpack_dir = dirname( __FILE__ ) . '/../../powerpack';

        error_log($this->settings['selected_deployment_option']);

        switch ( $this->settings['selected_deployment_option'] ) {
            case 'folder':
                error_log('folder deploy, already done');
                break;
            case 'zip':
                error_log('zip deploy, already done');
                break;
            case 's3':
                error_log('S3 deployment...');
                require_once $powerpack_dir . '/S3.php';
                
                if ( $test ) {
                    error_log('testing s3 deploy');
                    $s3->test_s3();
                    return;
                }

                $s3->prepare_deployment();
                $s3->transfer_files();
                $s3->cloudfront_invalidate_all_items();
                break;
            case 'bitbucket':
                error_log('Bitbucket deployment...');

                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/SitePublisher.php';

                require_once $powerpack_dir . '/Bitbucket.php';
                
                if ( $test ) {
                    error_log('testing bitbucket deploy');
                    $bitbucket->test_blob_create();
                    return;
                }

                $bitbucket->prepare_export();
                $bitbucket->upload_files();
                break;
            case 'bunnycdn':
                error_log('BunnyCDN deployment...');

                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/SitePublisher.php';

                require_once $powerpack_dir . '/BunnyCDN.php';
                
                if ( $test ) {
                    error_log('testing BunnyCDN deploy');
                    $bunny->test_deploy();
                    return;
                }

                $bunny->prepare_export();
                $bunny->transfer_files();
                $bunny->purge_all_cache();
                break;
            case 'ftp':
                error_log('FTP deployment...');

                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/SitePublisher.php';

                require_once $powerpack_dir . '/FTP.php';
                
                if ( $test ) {
                    error_log('testing FTP deploy');
                    $ftp->test_ftp();
                    return;
                }

                $ftp->prepare_export();
                $ftp->transfer_files();
                break;
            case 'github':
                error_log('GitHub deployment...');

                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/SitePublisher.php';

                require_once $powerpack_dir . '/GitHub.php';
                
                if ( $test ) {
                    error_log('testing GitHub deploy');
                    $github->test_blob_create();
                    return;
                }

                $github->prepare_export();
                $github->upload_blobs();
                $github->commit_new_tree();
                break;
            case 'gitlab':
                error_log('GitLab deployment...');

                require_once dirname( __FILE__ ) .
                    '/../StaticHtmlOutput/SitePublisher.php';

                require_once $powerpack_dir . '/GitLab.php';
                
                if ( $test ) {
                    error_log('testing GitLab deploy');
                    $gitlab->test_file_create();
                    return;
                }

                $gitlab->prepare_deployment();
                $gitlab->upload_files();
                break;
            case 'netlify':
                error_log('Netlify deployment...');

                require_once $powerpack_dir . '/Netlify.php';
                
                if ( $test ) {
                    error_log('testing Netlify deploy');
                    $netlify->test_netlify();
                    return;
                }

                $netlify->deploy();
                break;
        }

        // TODO: email upon successful cron deploy
        $this->emailDeployNotification();
    }

    public function emailDeployNotification() {
        $current_user = wp_get_current_user();
        $to = $current_user->user_email;
        $subject = 'Static site deployment: ' .
            $site_title = get_bloginfo( 'name' );
        $body = 'Your WordPress site has been automatically deployed.';
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }
}
