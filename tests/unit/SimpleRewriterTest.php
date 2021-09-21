<?php

namespace WP2Static;

use Mockery;
use org\bovigo\vfs\vfsStream;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SimpleRewriterTest extends TestCase {

    public function setUp() : void
    {
        WP_Mock::setUp();

        // Mock the methods and functions used by SimpleRewriter
        Mockery::mock( 'overload:\WP2Static\URLHelper' )
            ->shouldreceive( 'getProtocolRelativeURL' )
            ->andReturnUsing( [ $this, 'getProtocolRelativeURL' ] );
    }

    public function tearDown() : void
    {
        WP_Mock::tearDown();
        Mockery::close();
    }

    public static function coreOptionsMock() : \Mockery\CompositeExpectation {
        return Mockery::mock( 'overload:\WP2Static\CoreOptions' )
                      ->shouldReceive( 'getValue' )
                      ->withArgs( [ 'skipURLRewrite' ] )
                      ->andReturn( '0' )
                      ->shouldreceive( 'getLineDelimitedBlobValue' )
                      ->withArgs( [ 'hostsToRewrite' ] )
                      ->andReturn( [ 'localhost' ] );
    }

    /**
     * Test deleteDirWithFiles method
     *
     * @todo Add test for rewriting a file that doesn't exist
     *
     * @return void
     */
    public function testRewrite() {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' );

        // Set up a virual file to rewriting
        $structure = [
            'my-file.html' => 'my-file.html',
        ];
        $vfs = vfsStream::setup( 'root' );
        vfsStream::create( $structure, $vfs );
        $filepath = vfsStream::url( 'root/my-file.html' );

        // We're performing a rewrite and updating the file correctly
        file_put_contents( $filepath, 'https://foo.com' );
        SimpleRewriter::rewrite( $filepath );
        $expected = 'https://bar.com';
        $actual = file_get_contents( $filepath );
        $this->assertEquals( $expected, $actual );
    }

    public function rewriteFileContentsProvider() {
        return [
            'no changes needed' => [
                'a file with no change needed https://baz.com',
                'a file with no change needed https://baz.com',
            ],
            'WP to Destination URL (without trailing slash)' => [
                'https://foo.com',
                'https://bar.com',
            ],
            'WP to Destination URL (with trailing slash)' => [
                'https://foo.com/',
                'https://bar.com/',
            ],
            'multiple URLs' => [
                'multiple https://foo.com occurances https://foo.com present',
                'multiple https://bar.com occurances https://bar.com present',
            ],
            'URL with params' => [
                'https://foo.com/bar/baz',
                'https://bar.com/bar/baz',
            ],
            'URLs are not being cleaned correctly. Is this OK?' => [
                'https://foo.com//bar/baz',
                'https://bar.com//bar/baz',
            ],
            'Protocol relative URLs' => [
                '//foo.com/bar/baz',
                '//bar.com/bar/baz',
            ],
        ];
    }

    /**
     * @dataProvider rewriteFileContentsProvider
     */
    public function testRewriteFileContents( $raw_html, $expected ) {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' );

        $actual = SimpleRewriter::rewriteFileContents( $raw_html );
        $this->assertEquals( $expected, $actual );

        // Do a cslashed version of this test also
        $actual = SimpleRewriter::rewriteFileContents( addcslashes( $raw_html, '/' ) );
        $this->assertEquals( addcslashes( $expected, '/' ), $actual );
    }

    public function testRewriteFileContentsHttpToHttps() {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' )
            ->getMock();
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'http://foo.com/' )
            ->getMock();

        // http -> https
        $expected = 'https://bar.com/somepath';
        $actual = SimpleRewriter::rewriteFileContents( 'http://foo.com/somepath' );
        $this->assertEquals( $expected, $actual );
    }

    public function testRewriteFileContentsHttpsToHttp() {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'http://bar.com' )
            ->getMock();
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' )
            ->getMock();

        // http -> https
        $expected = 'http://bar.com/somepath';
        $actual = SimpleRewriter::rewriteFileContents( 'https://foo.com/somepath' );
        $this->assertEquals( $expected, $actual );
    }

    public function testRewriteFileContentsSkipURLRewrite() {
        // Mock the methods and functions used by SimpleRewriter
        Mockery::mock( 'overload:\WP2Static\CoreOptions' )
               ->shouldReceive( 'getValue' )
               ->withArgs( [ 'skipURLRewrite' ] )
               ->andReturn( '1' )
               ->shouldreceive( 'getLineDelimitedBlobValue' )
               ->withArgs( [ 'hostsToRewrite' ] )
               ->andReturn( [ 'localhost' ] )
               ->shouldreceive( 'getValue' )
               ->withArgs( [ 'deploymentURL' ] )
               ->andReturn( 'http://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' );

        $expected = 'https://foo.com/somepath';
        $actual = SimpleRewriter::rewriteFileContents( $expected );
        $this->assertEquals( $expected, $actual );
    }

    public function testRewriteFileContentsHostsToRewrite() {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'http://bar.com' )
            ->getMock();
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' )
            ->getMock();

        // localhost -> bar.com
        $expected = 'http://bar.com/somepath';
        $actual = SimpleRewriter::rewriteFileContents( 'https://localhost/somepath' );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * @dataProvider rewriteFileContentsProvider
     */
    public function testRewriteFileContentsDestinationUrlFilter( $raw_html, $expected ) {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' );

        // Test a deployment URL on a subdirectory
        \WP_Mock::onFilter( 'wp2static_set_destination_url' )
            ->with( 'https://bar.com' )
            ->reply( 'https://bar.com/somepath' );

        $expected = str_replace( 'bar.com', 'bar.com/somepath', $expected );

        $actual = SimpleRewriter::rewriteFileContents( $raw_html );
        $this->assertEquals( $expected, $actual );

        // Do a cslashed version of this test also
        $actual = SimpleRewriter::rewriteFileContents( addcslashes( $raw_html, '/' ) );
        $this->assertEquals( addcslashes( $expected, '/' ), $actual );
    }

    /**
     * @dataProvider rewriteFileContentsProvider
     */
    public function testRewriteFileContentsSiteUrlFilter( $raw_html, $expected ) {
        // Mock the methods and functions used by SimpleRewriter
        self::coreOptionsMock()
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com/' );

        // Test a deployment URL on a subdirectory
        \WP_Mock::onFilter( 'wp2static_set_wordpress_site_url' )
            ->with( 'https://foo.com' )
            ->reply( 'https://foo.com/somepath/' );

        $raw_html = str_replace( 'foo.com', 'foo.com/somepath', $raw_html );

        $actual = SimpleRewriter::rewriteFileContents( $raw_html );
        $this->assertEquals( $expected, $actual );

        // Do a cslashed version of this test also
        $actual = SimpleRewriter::rewriteFileContents( addcslashes( $raw_html, '/' ) );
        $this->assertEquals( addcslashes( $expected, '/' ), $actual );
    }

    /**
     * Reimplimentation of URLHelper::getProtocolRelativeURL specific for our
     * test.
     *
     * @param string $url
     * @return string
     */
    public function getProtocolRelativeURL( string $url ): string {
        return str_replace(
            [
                'https:',
                'http:',
            ],
            [
                '',
                '',
            ],
            $url
        );
    }
}
