<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;

class FilesHelper {

    /**
     * Recursively delete a directory
     *
     * @throws WP2StaticException
     */
    public static function deleteDirWithFiles( string $dir ) : void {
        if ( is_dir( $dir ) ) {
            $dir_files = scandir( $dir );

            if ( ! $dir_files ) {
                $err = 'Trying to delete nonexistent dir: ' . $dir;
                WsLog::l( $err );
                throw new WP2StaticException( $err );
            }

            $files = array_diff( $dir_files, [ '.', '..' ] );

            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ?
                self::deleteDirWithFiles( "$dir/$file" ) :
                unlink( "$dir/$file" );
            }

            rmdir( $dir );
        }
    }

    /**
     * Get public URLs for all files in a local directory.
     *
     * @param string $dir
     * @param array<string> $filenames_to_ignore
     * @param array<string> $file_extensions_to_ignore
     * @return string[] list of relative, urlencoded URLs
     */
    public static function getListOfLocalFilesByDir(
        string $dir,
        array $filenames_to_ignore,
        array $file_extensions_to_ignore
    ) : array {
        $site_path = SiteInfo::getPath( 'site' );

        if ( ! is_string( $site_path ) ) {
            return [];
        }

        $files = [];

        if ( is_dir( $dir ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::pathLooksCrawlable(
                    $filename,
                    $filenames_to_ignore,
                    $file_extensions_to_ignore
                );

                if ( $path_crawlable ) {
                    $url = str_replace( $site_path, '/', $filename );

                    if ( is_string( $url ) ) {
                        $files[] = $url;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Ensure a given filepath has an allowed filename and extension.
     *
     * @param string $file_name
     * @param array<string> $filenames_to_ignore
     * @param array<string> $file_extensions_to_ignore
     * @return bool  True if the given file does not have a disallowed filename
     *               or extension.
     */
    public static function pathLooksCrawlable(
        string $file_name,
        array $filenames_to_ignore,
        array $file_extensions_to_ignore
    ) : bool {
        $filename_matches = 0;

        str_ireplace( $filenames_to_ignore, '', $file_name, $filename_matches );

        // If we found matches we don't need to go any further
        if ( $filename_matches ) {
            return false;
        }

        /*
          Prepare the file extension list for regex:
          - Add prepending (escaped) \ for a literal . at the start of
            the file extension
          - Add $ at the end to match end of string
          - Add i modifier for case insensitivity
        */
        foreach ( $file_extensions_to_ignore as $extension ) {
            if ( preg_match( "/\\{$extension}$/i", $file_name ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure a given filepath has an allowed filename and extension.
     *
     * @return bool  True if the given file does not have a disallowed filename
     *               or extension.
     */
    public static function filePathLooksCrawlable( string $file_name ) : bool {
        $filenames_to_ignore = CoreOptions::getLineDelimitedBlobValue( 'filenamesToIgnore' );

        $filenames_to_ignore =
            apply_filters(
                'wp2static_filenames_to_ignore',
                $filenames_to_ignore
            );

        $file_extensions_to_ignore = CoreOptions::getLineDelimitedBlobValue(
            'fileExtensionsToIgnore'
        );

        $file_extensions_to_ignore =
            apply_filters(
                'wp2static_file_extensions_to_ignore',
                $file_extensions_to_ignore
            );

        return self::pathLooksCrawlable(
            $file_name,
            $filenames_to_ignore,
            $file_extensions_to_ignore
        );
    }

    /**
     * Clean all detected URLs before use. Accepts relative and absolute URLs
     * both with and without starting or trailing slashes.
     *
     * @param string[] $urls list of absolute or relative URLs
     * @return string[]|null[] list of relative URLs
     * @throws WP2StaticException
     */
    public static function cleanDetectedURLs( array $urls ) : array {
        $home_url = SiteInfo::getUrl( 'home' );

        if ( ! is_string( $home_url ) ) {
            $err = 'Home URL not defined ';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $cleaned_urls = array_map(
            // trim hashes/query strings
            function ( $url ) use ( $home_url ) {
                if ( ! $url ) {
                    return;
                }

                // NOTE: 2 x str_replace's significantly faster than
                // 1 x str_replace with search/replace arrays of 2 length
                $url = str_replace(
                    $home_url,
                    '/',
                    $url
                );

                $url = str_replace(
                    '//',
                    '/',
                    $url
                );

                if ( ! is_string( $url ) ) {
                    return;
                }

                $url = strtok( $url, '#' );

                if ( ! $url ) {
                    return;
                }

                $url = strtok( $url, '?' );

                if ( ! $url ) {
                    return;
                }

                return $url;
            },
            $urls
        );

        if ( empty( $cleaned_urls ) ) {
            $err = 'No valid URLs left after cleaning';
            WsLog::l( $err );
            return [];
        }

        return $cleaned_urls;
    }
}
