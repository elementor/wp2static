<?php

namespace WP2Static;

class PostProcessElementURLStructure {

    public function __construct() {
    }

    /*
     * Apply post-processing to URLs such as converting to doc-root relative
     * or site-root relative
     *
     * At this point, $url_within_page has been rewritten to Destination domain
     *
     * @return string Rewritten URL
     *
    */
    public function postProcessElementURLStructure(
        string $url_within_page,
        string $url_of_page_being_processed
    ) : string {
        // error_log($url_within_page);
        // error_log($url_of_page_being_processed);die();
        

        if ( ExportSettings::get( 'useDocumentRelativeURLs' ) ) {
            return ConvertToDocumentRelativeURL::convert(
                $url_within_page,
                $url_of_page_being_processed,
                ExportSettings::get( 'destination_url' ),
                ExportSettings::get( 'allowOfflineUsage' )
            );
        }

        if ( ExportSettings::get( 'useSiteRootRelativeURLs' ) ) {
            return ConvertToSiteRootRelativeURL::convert(
                $url_within_page,
                ExportSettings::get( 'destination_url' )
            );
        }

        return '';
    }
}
