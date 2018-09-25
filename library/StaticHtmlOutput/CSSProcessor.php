<?php
/**
 * CSSProcessor
 *
 * @package WP2Static
 */
class CSSProcessor {

    /**
     * Constructor
     *
     * @param string $css_document CSS document
     * @param string $wp_site_url  Site URL
     */
    public function __construct( $css_document, $wp_site_url ) {
        $this->wp_site_url = $wp_site_url;

        // parse CSS into easily modifiable form
        $path_parser = dirname( __FILE__ ) . '/../CSSParser/';
        require_once $path_parser . 'Parser.php';
        require_once $path_parser . 'Settings.php';
        require_once $path_parser . 'Renderable.php';
        require_once $path_parser . 'OutputFormat.php';
        require_once $path_parser . 'Comment/Comment.php';
        require_once $path_parser . 'Comment/Commentable.php';
        require_once $path_parser . 'Parsing/SourceException.php';
        require_once $path_parser . 'Parsing/OutputException.php';
        require_once $path_parser . 'Parsing/UnexpectedTokenException.php';
        require_once $path_parser . 'Property/AtRule.php';
        require_once $path_parser . 'Property/Charset.php';
        require_once $path_parser . 'Property/CSSNamespace.php';
        require_once $path_parser . 'Property/Import.php';
        require_once $path_parser . 'Property/Selector.php';
        require_once $path_parser . 'RuleSet/RuleSet.php';
        require_once $path_parser . 'Rule/Rule.php';
        require_once $path_parser . 'RuleSet/AtRuleSet.php';
        require_once $path_parser . 'RuleSet/DeclarationBlock.php';
        require_once $path_parser . 'Value/Value.php';
        require_once $path_parser . 'Value/ValueList.php';
        require_once $path_parser . 'Value/RuleValueList.php';
        require_once $path_parser . 'Value/CSSFunction.php';
        require_once $path_parser . 'Value/CalcFunction.php';
        require_once $path_parser . 'Value/CalcRuleValueList.php';
        require_once $path_parser . 'Value/PrimitiveValue.php';
        require_once $path_parser . 'Value/Color.php';
        require_once $path_parser . 'Value/CSSString.php';
        require_once $path_parser . 'Value/LineName.php';
        require_once $path_parser . 'Value/Size.php';
        require_once $path_parser . 'Value/URL.php';
        require_once $path_parser . 'CSSList/CSSList.php';
        require_once $path_parser . 'CSSList/CSSBlockList.php';
        require_once $path_parser . 'CSSList/AtRuleBlockList.php';
        require_once $path_parser . 'CSSList/Document.php';
        require_once $path_parser . 'CSSList/KeyFrame.php';

        $oCssParser = new Sabberworm\CSS\Parser( $css_document );
        $this->css_doc = $oCssParser->parse();
    }


    /**
     * Check whether link is internal
     *
     * @param string $link Link
     * @return boolean
     */
    public function isInternalLink( $link ) {
        // check link is same host as $this->url and not a subdomain
        $url_host = parse_url( $link, PHP_URL_HOST );
        $url_wp   = parse_url( $this->wp_site_url, PHP_URL_HOST );
        return $url_host == $url_wp;
    }


    /**
     * Normalize URL
     *
     * @param string $url URL
     * @return void
     */
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


    /**
     * Cleanup
     *
     * @param string $wp_site_environment    Site environment
     * @param array  $overwrite_slug_targets Target slugs
     * @return void
     */
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


    /**
     * Get CSS
     *
     * @return string
     */
    public function getCSS() {
        return $this->css_doc->render();
    }

}
