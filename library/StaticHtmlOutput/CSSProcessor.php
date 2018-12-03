<?php


class CSSProcessor {

    public function processCSS( $css_document, $page_url ) {
        if ( $css_document == '' ) {
            return false;
        }

        $target_settings = array(
            'general',
            'crawling',
            'wpenv',
            'processing',
            'advanced',
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

        $this->discoverNewURLs = (
            isset( $this->settings['discoverNewURLs'] ) &&
             $this->settings['discoverNewURLs'] == 1 &&
             $_POST['ajax_action'] === 'crawl_site'
        );

        $this->discovered_urls = [];

        // parse CSS into easily modifiable form
        $path = dirname( __FILE__ ) . '/../CSSParser/';
        require_once $path . 'Parser.php';
        require_once $path . 'Settings.php';
        require_once $path . 'Renderable.php';
        require_once $path . 'OutputFormat.php';
        require_once $path . 'Comment/Comment.php';
        require_once $path . 'Comment/Commentable.php';
        require_once $path . 'Parsing/SourceException.php';
        require_once $path . 'Parsing/OutputException.php';
        require_once $path . 'Parsing/UnexpectedTokenException.php';
        require_once $path . 'Property/AtRule.php';
        require_once $path . 'Property/Charset.php';
        require_once $path . 'Property/CSSNamespace.php';
        require_once $path . 'Property/Import.php';
        require_once $path . 'Property/Selector.php';
        require_once $path . 'RuleSet/RuleSet.php';
        require_once $path . 'Rule/Rule.php';
        require_once $path . 'RuleSet/AtRuleSet.php';
        require_once $path . 'RuleSet/DeclarationBlock.php';
        require_once $path . 'Value/Value.php';
        require_once $path . 'Value/ValueList.php';
        require_once $path . 'Value/RuleValueList.php';
        require_once $path . 'Value/CSSFunction.php';
        require_once $path . 'Value/CalcFunction.php';
        require_once $path . 'Value/CalcRuleValueList.php';
        require_once $path . 'Value/PrimitiveValue.php';
        require_once $path . 'Value/Color.php';
        require_once $path . 'Value/CSSString.php';
        require_once $path . 'Value/LineName.php';
        require_once $path . 'Value/Size.php';
        require_once $path . 'Value/URL.php';
        require_once $path . 'CSSList/CSSList.php';
        require_once $path . 'CSSList/CSSBlockList.php';
        require_once $path . 'CSSList/AtRuleBlockList.php';
        require_once $path . 'CSSList/Document.php';
        require_once $path . 'CSSList/KeyFrame.php';

        $oCssParser = new Sabberworm\CSS\Parser( $css_document );
        $this->css_doc = $oCssParser->parse();

        return true;
    }

    public function isInternalLink( $link, $domain = false ) {
        if ( ! $domain ) {
            $domain = $this->settings['wp_site_url'];
        }

        // TODO: apply only to links starting with .,..,/,
        // or any with just a path, like banana.png
        // check link is same host as $this->url and not a subdomain
        return parse_url( $link, PHP_URL_HOST ) === parse_url(
            $domain,
            PHP_URL_HOST
        );
    }

    public function normalizeURLs( $url ) {
        require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
        $base = new Net_URL2( $url );

        foreach ( $this->css_doc->getAllValues() as $mValue ) {
            if ( $mValue instanceof Sabberworm\CSS\Value\URL ) {
                $original_link = $mValue->getURL();

                // TODO: benchmark trim vs str_replace
                // returned value contains surrounding quotes
                $original_link = trim( trim( $original_link, "'" ), '"' );

                if ( $this->isInternalLink( $original_link ) ) {
                    $absolute_url = new Sabberworm\CSS\Value\CSSString(
                        $base->resolve( $original_link )
                    );
                    $mValue->setURL( $absolute_url );
                }
            }
        }
    }

    public function cleanup( $wp_site_environment, $overwrite_slug_targets ) {
        // PERF: ~ 30ms for HTML or CSS
        // TODO: skip binary file processing in func
        // TODO: move to CSSProcessor
        if ( $this->isCSS() ) {
            $regex = array(
                "`^([\t\s]+)`ism" => '',
                '`^\/\*(.+?)\*\/`ism' => '',
                "`([\n\A;]+)\/\*(.+?)\*\/`ism" => '$1',
                "`([\n\A;\s]+)//(.+?)[\n\r]`ism" => "$1\n",
                "`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism" => "\n",
            );

            $rewritten_CSS = preg_replace(
                array_keys( $regex ),
                $regex,
                $this->response['body']
            );

            $this->setResponseBody( $rewritten_CSS );
        }
    }

    public function getCSS() {
        return $this->css_doc->render();
    }
}

