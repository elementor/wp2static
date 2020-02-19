<?php

namespace WP2Static;

class AssetDownloader {

    private $ch;

    /**
     * Create AssetDownloader
     *
     * @param resource $ch cURL handle
     */
    public function __construct( $ch ) {
        $this->ch = $ch;
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

        /*
         TODO: copied these from ExportSettings

            $crawlable_filetypes = [];
            $crawlable_filetypes['img'] = 1;
            $crawlable_filetypes['jpeg'] = 1;
            $crawlable_filetypes['jpg'] = 1;
            $crawlable_filetypes['png'] = 1;
            $crawlable_filetypes['webp'] = 1;
            $crawlable_filetypes['gif'] = 1;
            $crawlable_filetypes['svg'] = 1;

            // properties which should not change during plugin execution
            self::$settings = [
                'crawlable_filetypes' => $crawlable_filetypes,
            ];
        */

        // check if supported filetype for crawling
        if ( isset( CoreOptions::getValue( 'crawlable_filetypes' )[ $extension ] ) ) {
            // skip if in Crawl Cache already
            if ( ! CoreOptions::getValue( 'dontUseCrawlCaching' ) ) {
                if ( CrawlCache::getUrl( $url ) ) {
                    return;
                }
            }

            // get url without Site URL
            $save_path = str_replace(
                SiteInfo::getUrl( 'site_url' ),
                '',
                $url
            );

            $filename = SiteInfo::getPath( 'uploads' ) .
                'wp2static-exported-site/' .
                $save_path;

            $curl_options = [];

            if ( CoreOptions::getValue( 'crawlPort' ) ) {
                $curl_options[ CURLOPT_PORT ] =
                    CoreOptions::getValue( 'crawlPort' );
            }

            if ( CoreOptions::getValue( 'crawlUserAgent' ) ) {
                $curl_options[ CURLOPT_USERAGENT ] =
                    CoreOptions::getValue( 'crawlUserAgent' );
            }

            if ( CoreOptions::getValue( 'useBasicAuth' ) ) {
                $curl_options[ CURLOPT_USERPWD ] =
                    CoreOptions::getValue( 'basicAuthUser' ) . ':' .
                    CoreOptions::getValue( 'basicAuthPassword' );
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
