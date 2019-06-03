<?php

namespace WP2Static;

class AssetDownloader {

    private $ch;
    private $settings;
    private $site_url;
    private $crawlable_filetypes;

    public function __construct() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    /*
     * Download discovered assets
     *
     * @param string $url Absolute local URL to potentially download
     */
    public function downloadAsset( string $url, string $extension ) : void {
        // check if user wants to download discovered assets

        // TODO: add local cache per iteration of HTMLProcessor to
        // faster skip cached files without querying DB

        // check if supported filetype for crawling
        if ( isset( $this->crawlable_filetypes[ $extension ] ) ) {
            // skip if in Crawl Cache already
            if ( ! isset( $this->settings['dontUseCrawlCaching'] ) ) {
                if ( CrawlCache::getUrl( $url ) ) {
                    return;
                }
            }

            // get url without Site URL
            $save_path = str_replace(
                $this->site_url,
                '',
                $url
            );

            $filename = SiteInfo::getPath( 'uploads' ) .
                'wp2static-exported-site/' .
                $save_path;

            $curl_options = [];

            if ( isset( $this->settings['crawlPort'] ) ) {
                $curl_options[ CURLOPT_PORT ] =
                    $this->settings['crawlPort'];
            }

            if ( isset( $this->settings['crawlUserAgent'] ) ) {
                $curl_options[ CURLOPT_USERAGENT ] =
                    $this->settings['crawlUserAgent'];
            }

            if ( isset( $this->settings['useBasicAuth'] ) ) {
                $curl_options[ CURLOPT_USERPWD ] =
                    $this->settings['basicAuthUser'] . ':' .
                    $this->settings['basicAuthPassword'];
            }

            $request = new Request();

            $response = $request->getURL(
                $url,
                $this->ch,
                $curl_options
            );

            if ( is_array( $response ) ) {
                $ch = $response['ch'];
                $body = $response['body'];
            }

            $basename = basename( $filename );

            $dir_without_filename = str_replace(
                $basename,
                $filename,
                $filename
            );

            if ( ! is_dir( $dir_without_filename ) ) {
                wp_mkdir_p( $dir_without_filename );
            }

            if ( ! isset( $body ) ) {
                return;
            }

            $result = file_put_contents(
                $filename,
                $body
            );

            if ( ! $result ) {
                $err = 'Error attempting to save' . $filename;
                WsLog::l( $err );

                return;
            }

            CrawlCache::addUrl( $url );
        }
    }
}
