<?php
/**
 * Deployer
 *
 * @package WP2Static
 */
class Deployer {


    /**
     * Constructor
     */
    public function __construct() {

        switch ( $this->selected_deployment_option ) {
            case 'folder':
                $this->copyStaticSiteToPublicFolder();
                break;

            case 'github':
                $this->githubRepo = filter_input( INPUT_POST, 'githubRepo' );
                $this->githubPersonalAccessToken = filter_input(
                    INPUT_POST,
                    'githubPersonalAccessToken'
                );
                $this->githubBranch = filter_input(
                    INPUT_POST,
                    'githubBranch'
                );
                $this->githubPath = filter_input( INPUT_POST, 'githubPath' );

                $this->github_prepare_export();
                $this->github_upload_blobs( true );
                $this->github_finalise_export();
                break;

            case 'ftp':
                $this->ftpServer = filter_input( INPUT_POST, 'ftpServer' );
                $this->ftpUsername = filter_input( INPUT_POST, 'ftpUsername' );
                $this->ftpPassword = filter_input( INPUT_POST, 'ftpPassword' );
                $this->ftpRemotePath = filter_input(
                    INPUT_POST,
                    'ftpRemotePath'
                );
                $this->useActiveFTP = filter_input(
                    INPUT_POST,
                    'useActiveFTP'
                );

                $this->ftp_prepare_export();
                $this->ftp_transfer_files( true );
                break;

            case 'netlify':
                $this->netlifySiteID = filter_input(
                    INPUT_POST,
                    'netlifySiteID'
                );
                $this->netlifyPersonalAccessToken = filter_input(
                    INPUT_POST,
                    'netlifyPersonalAccessToken'
                );

                $this->create_zip();
                $this->netlify_do_export();
                break;

            case 'zip':
                $this->create_zip();
                break;

            case 's3':
                $this->s3Key = filter_input( INPUT_POST, 's3Key' );
                $this->s3Secret = filter_input( INPUT_POST, 's3Secret' );
                $this->s3Region = filter_input( INPUT_POST, 's3Region' );
                $this->s3Bucket = filter_input( INPUT_POST, 's3Bucket' );
                $this->s3RemotePath = filter_input(
                    INPUT_POST,
                    's3RemotePath'
                );
                $this->cfDistributionId = filter_input(
                    INPUT_POST,
                    'cfDistributionId'
                );

                $this->s3_prepare_export();
                $this->s3_transfer_files( true );
                $this->cloudfront_invalidate_all_items();
                break;

            case 'bunnycdn':
                $this->bunnycdnPullZoneName = filter_input(
                    INPUT_POST,
                    'bunnycdnPullZoneName'
                );
                $this->bunnycdnAPIKey = filter_input(
                    INPUT_POST,
                    'bunnycdnAPIKey'
                );
                $this->bunnycdnRemotePath = filter_input(
                    INPUT_POST,
                    'bunnycdnRemotePath'
                );

                $this->bunnycdn_prepare_export();
                $this->bunnycdn_transfer_files( true );
                break;

            case 'dropbox':
                $this->dropboxAccessToken = filter_input(
                    INPUT_POST,
                    'dropboxAccessToken'
                );
                $this->dropboxFolder = filter_input(
                    INPUT_POST,
                    'dropboxFolder'
                );

                $this->dropbox_prepare_export();
                $this->dropbox_do_export( true );
                break;
        }//end switch

        error_log( 'scheduled deploy complete' );
        /**
         * TODO: email upon successful cron deploy
         *
        $current_user = wp_get_current_user();
        $to = $current_user->user_email;
        $subject = 'Static site deployment: ' .
            $site_title = get_bloginfo( 'name' );;
        $body = 'Your WordPress site has been automatically deployed.';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail( $to, $subject, $body, $headers );
         */

    }


    /**
     * Prepare S3 export
     *
     * @return void
     */
    public function s3_prepare_export() {
        if ( wpsho_fr()->is__premium_only() ) {

            $s3 = new StaticHtmlOutput_S3(
                $this->s3Key,
                $this->s3Secret,
                $this->s3Region,
                $this->s3Bucket,
                $this->s3RemotePath,
                $this->getWorkingDirectory()
            );

            $s3->prepare_deployment();
        }
    }


    /**
     * Transfer files to S3
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function s3_transfer_files( $viaCLI = false ) {
        if ( wpsho_fr()->is__premium_only() ) {

            $s3 = new StaticHtmlOutput_S3(
                $this->s3Key,
                $this->s3Secret,
                $this->s3Region,
                $this->s3Bucket,
                $this->s3RemotePath,
                $this->getWorkingDirectory()
            );

            $s3->transfer_files( $viaCLI );
        }
    }


    /**
     * Invalidate items at CloudFront
     *
     * @return void
     */
    public function cloudfront_invalidate_all_items() {
        if ( wpsho_fr()->is__premium_only() ) {
            require_once __DIR__ . '/CloudFront/CloudFront.php';
            $cloudfront_id = $this->cfDistributionId;

            if ( ! empty( $cloudfront_id ) ) {

                $cf = new CloudFront(
                    $this->s3Key,
                    $this->s3Secret,
                    $cloudfront_id
                );

                $cf->invalidate( '/*' );

                if (
                    200 === $cf->getResponseMessage() ||
                    201 === $cf->getResponseMessage()
                ) {
                    echo 'SUCCESS';
                } else {
                    WsLog::l( 'CF ERROR: ' . $cf->getResponseMessage() );
                }
            } else {
                echo 'SUCCESS';
            }//end if
        }//end if
    }


    /**
     * Prepare Dropbox export
     *
     * @return void
     */
    public function dropbox_prepare_export() {
        $dropbox = new StaticHtmlOutput_Dropbox(
            $this->dropboxAccessToken,
            $this->dropboxFolder,
            $this->getWorkingDirectory()
        );

        $dropbox->prepare_export();
    }


    /**
     * Export to Dropbox
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function dropbox_do_export( $viaCLI = false ) {
        $dropbox = new StaticHtmlOutput_Dropbox(
            $this->dropboxAccessToken,
            $this->dropboxFolder,
            $this->getWorkingDirectory()
        );

        $dropbox->transfer_files( $viaCLI );
    }


    /**
     * Export to Netlify
     *
     * @return void
     */
    public function netlify_do_export() {
        // will exclude the siteroot when copying
        $archiveDir = file_get_contents(
            $this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'
        );
        $archiveName = rtrim( $archiveDir, '/' ) . '.zip';

        $netlify = new StaticHtmlOutput_Netlify(
            $this->netlifySiteID,
            $this->netlifyPersonalAccessToken
        );

        echo $netlify->deploy( $archiveName );
    }


    /**
     * Prepare file list
     *
     * @param string $export_target Export target
     * @return void
     */
    public function prepare_file_list( $export_target ) {
        $file_list_path = $this->getWorkingDirectory() .
            '/WP-STATIC-EXPORT-' . $export_target . '-FILES-TO-EXPORT';

        // zero file
        $f = @fopen( $file_list_path, 'r+' );
        if ( $f !== false ) {
            ftruncate( $f, 0 );
            fclose( $f );
        }

        $archiveDir = file_get_contents(
            $this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'
        );
        $archiveName = rtrim( $archiveDir, '/' );
        $siteroot = $archiveName . '/';

        error_log( 'preparing file list' );

        StaticHtmlOutput_FilesHelper::recursively_scan_dir(
            $siteroot,
            $siteroot,
            $file_list_path
        );
    }


    /**
     * Prepare for FTP export
     *
     * @return void
     */
    public function ftp_prepare_export() {
        $ftp = new StaticHtmlOutput_FTP(
            $this->ftpServer,
            $this->ftpUsername,
            $this->ftpPassword,
            $this->ftpRemotePath,
            $this->useActiveFTP,
            $this->getWorkingDirectory()
        );

        $ftp->prepare_deployment();
    }


    /**
     * Transfer files via FTP
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function ftp_transfer_files( $viaCLI = false ) {
        $ftp = new StaticHtmlOutput_FTP(
            $this->ftpServer,
            $this->ftpUsername,
            $this->ftpPassword,
            $this->ftpRemotePath,
            $this->useActiveFTP,
            $this->getWorkingDirectory()
        );

        $ftp->transfer_files( $viaCLI );
    }


    /**
     * Prepare for export to BunnyCDN
     *
     * @return void
     */
    public function bunnycdn_prepare_export() {
        if ( wpsho_fr()->is__premium_only() ) {
            $bunnyCDN = new StaticHtmlOutput_BunnyCDN(
                $this->bunnycdnPullZoneName,
                $this->bunnycdnAPIKey,
                $this->bunnycdnRemotePath,
                $this->getWorkingDirectory()
            );

            $bunnyCDN->prepare_export();
        }
    }


    /**
     * Transfer files to BunnyCDN
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function bunnycdn_transfer_files( $viaCLI = false ) {
        if ( wpsho_fr()->is__premium_only() ) {

            $bunnyCDN = new StaticHtmlOutput_BunnyCDN(
                $this->bunnycdnPullZoneName,
                $this->bunnycdnAPIKey,
                $this->bunnycdnRemotePath,
                $this->getWorkingDirectory()
            );

            $bunnyCDN->transfer_files( $viaCLI );
        }
    }


    /**
     * Purge BunnyCDN cache
     *
     * @return void
     */
    public function bunnycdn_purge_cache() {
        if ( wpsho_fr()->is__premium_only() ) {

            $bunnyCDN = new StaticHtmlOutput_BunnyCDN(
                $this->bunnycdnPullZoneName,
                $this->bunnycdnAPIKey,
                $this->bunnycdnRemotePath,
                $this->getWorkingDirectory()
            );

            $bunnyCDN->purge_all_cache();
        }
    }


    /**
     * Upload blogs
     *
     * @param boolean $viaCLI CLI flag
     * @return void
     */
    public function github_upload_blobs( $viaCLI = false ) {
        $github = new StaticHtmlOutput_GitHub(
            $this->githubRepo,
            $this->githubPersonalAccessToken,
            $this->githubBranch,
            $this->githubPath,
            $this->getWorkingDirectory()
        );

        $github->upload_blobs( $viaCLI );
    }


    /**
     * Prepare GitHub export
     *
     * @return void
     */
    public function github_prepare_export() {
        $github = new StaticHtmlOutput_GitHub(
            $this->githubRepo,
            $this->githubPersonalAccessToken,
            $this->githubBranch,
            $this->githubPath,
            $this->getWorkingDirectory()
        );

        $github->prepare_deployment();
    }


    /**
     * Finalize GitHub export
     *
     * @return void
     */
    public function github_finalise_export() {
        $github = new StaticHtmlOutput_GitHub(
            $this->githubRepo,
            $this->githubPersonalAccessToken,
            $this->githubBranch,
            $this->githubPath,
            $this->getWorkingDirectory()
        );

        $github->commit_new_tree();
    }

}
