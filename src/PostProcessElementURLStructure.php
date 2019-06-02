<?php

namespace WP2Static;

class PostProcessElementURLStructure {

    private $settings;
    private $destination_url;

    /*
     * After we have normalized the element's URL and have an absolute
     * Placeholder URL, we can perform transformations, such as making it
     * an offline URL or document relative

     * site root relative URLs can be bulk rewritten before outputting HTML
     * so we don't both doing those here

     * We need to do while iterating the URLs, as we cannot accurately
     * iterate individual URLs in bulk rewriting mode and each URL
     * needs to be rewritten in a different manner for offline mode rewriting
     *
     * @param string $url absolute Site URL to change
     * @param string $page_url URL of current page for doc relative calculation
     * @param string $destination_url Site URL reference for rewriting
     * @return string Rewritten URL
     *
    */
    public function postProcessElementURLStructure(
        string $url,
        string $page_url,
        string $site_url
    ) : string {
        $offline_mode = false;

        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            $offline_mode = true;
        }

        // TODO: move detection func higher
        if ( isset( $this->settings['useDocumentRelativeURLs'] ) ) {
            $url = ConvertToDocumentRelativeURL::convert(
                $url,
                $page_url,
                $site_url,
                $offline_mode
            );
        }

        if ( isset( $this->settings['useSiteRootRelativeURLs'] ) ) {
            $url = ConvertToSiteRootRelativeURL::convert(
                $url,
                $this->destination_url
            );
        }

        return $url;
    }
}
