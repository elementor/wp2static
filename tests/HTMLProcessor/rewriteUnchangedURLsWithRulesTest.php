<?php

chdir( dirname(__FILE__) . '/../../plugin' );

$plugin_dir = getcwd();

require_once $plugin_dir . '/WP2Static/WP2Static.php';
require_once $plugin_dir . '/WP2Static/HTMLProcessor.php';
require_once $plugin_dir . '/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorRewriteUnchangedURLsWithRulesTest extends TestCase {

    /**
     * Test data provider
     * @dataProvider unchangedURLsProvider
     */
    public function testRewritingRemainingPlaceholders(
        $destination_protocol,
        $processed_html,
        $exp_result
        ) {

        // mock out only the unrelated methods
        $processor = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isInternalLink',
                    'loadSettings',
                    'getTargetSiteProtocol',
                ]
            )
            ->getMock();

        $processor->method( 'loadSettings' )->willReturn( null );
        $processor->settings = array();
        $processor->settings['baseUrl'] = 'http://somedomain.com';
        $processor->settings['baseUrl'] =
            $destination_protocol . 'somedomain.com';

        $processor->settings['rewriteRules'] = 
                "category,cat\n".
                "category/photo,cat/pics\n".
                "wp-content/plugins,modules\n".
                "wp-content/uploads,assets\n".
                "wp-content/themes,ui\n".
                "wp-includes,inc\n".
                "wp-admin,extra\n".
                "wp-content/plugins/myplugin,modules/plugin1";
   

        $processor->method( 'getTargetSiteProtocol' )->willReturn( $destination_protocol );

        $processor->placeholder_url =
            $destination_protocol . 'PLACEHOLDER.wpsho/';


        $processor->page_url = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );

        $this->assertEquals(
            $exp_result,
            $processor->rewriteUnchangedPlaceholderURLs( $processed_html )
        );
    }

    public function unchangedURLsProvider() {
        return [
           'rewrite category to cat' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/category/2019">Link to some file</a>',
                '<a href="http://somedomain.com/cat/2019">Link to some file</a>',
            ],
           'rewrite category/photos to cat/pics' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/category/photo">Link to some file</a>',
                '<a href="http://somedomain.com/cat/pics">Link to some file</a>',
            ],
            'rewrite wp-content/plugins to modules' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/wp-content/plugins">Link to some file</a>',
                '<a href="http://somedomain.com/modules">Link to some file</a>',
            ],
            'rewrite wp-content/plugins/myplugin to modules/plugin1' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/wp-content/plugins/myplugin">Link to some file</a>',
                '<a href="http://somedomain.com/modules/plugin1">Link to some file</a>',
            ],
            'rewrite wp-content/uploads to assets' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/wp-content/uploads/2019">Link to some file</a>',
                '<a href="http://somedomain.com/assets/2019">Link to some file</a>',
            ],
            'rewrite wp-content/themes to ui' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/wp-content/themes/mytheme">Link to some file</a>',
                '<a href="http://somedomain.com/ui/mytheme">Link to some file</a>',
            ],
            'rewrite wp-includes to inc' =>  [
                'http://',
                '<a href="http://PLACEHOLDER.wpsho/wp-includes">Link to some file</a>',
                '<a href="http://somedomain.com/inc">Link to some file</a>',
            ],

        ];
    }
}

