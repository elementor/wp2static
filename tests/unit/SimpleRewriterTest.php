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
    }

    public function tearDown() : void
    {
        WP_Mock::tearDown();
        Mockery::close();
    }

    /**
     * Test deleteDirWithFiles method
     *
     * @return void
     */
    public function testRewrite() {
        // Set up a virual file to rewriting
        $structure = [
            'my-file.html' => 'my-file.html',
        ];
        $vfs = vfsStream::setup( 'root' );
        vfsStream::create( $structure, $vfs );
        $filepath = vfsStream::url( 'root/my-file.html' );

        // Mock the methods and functions used by SimpleRewriter
        Mockery::mock( 'overload:\WP2Static\CoreOptions' )
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com' );
        Mockery::mock( 'overload:\WP2Static\URLHelper' )
            ->shouldreceive( 'getProtocolRelativeURL' )
            ->andReturnUsing( [ $this, 'getProtocolRelativeURL' ] );
        WP_Mock::userFunction(
            'trailingslashit',
            [
                'return_arg' => 0,
            ]
        );

        // We're performing a rewrite and updating the file correctly
        file_put_contents( $filepath, 'https://foo.com' );
        SimpleRewriter::rewrite( $filepath );
        $expected = 'https://bar.com';
        $actual = file_get_contents( $filepath );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test deleteDirWithFiles method
     *
     * @return void
     */
    public function testRewriteFileContents() {
        // Mock the methods and functions used by SimpleRewriter
        Mockery::mock( 'overload:\WP2Static\CoreOptions' )
            ->shouldreceive( 'getValue' )
            ->withArgs( [ 'deploymentURL' ] )
            ->andReturn( 'https://bar.com' );
        Mockery::mock( 'overload:\WP2Static\SiteInfo' )
            ->shouldreceive( 'getUrl' )
            ->withArgs( [ 'site' ] )
            ->andReturn( 'https://foo.com' );
        Mockery::mock( 'overload:\WP2Static\URLHelper' )
            ->shouldreceive( 'getProtocolRelativeURL' )
            ->andReturnUsing( [ $this, 'getProtocolRelativeURL' ] );
        WP_Mock::userFunction(
            'trailingslashit',
            [
                'return_arg' => 0,
            ]
        );

        $expected = 'a file with no change needed';
        $actual = SimpleRewriter::rewriteFileContents( 'a file with no change needed' );
        $this->assertEquals( $expected, $actual );

        // We're rewriting WP to Destination URL correctly (without trailing slash)
        $expected = 'https://bar.com';
        $actual = SimpleRewriter::rewriteFileContents( 'https://foo.com' );
        $this->assertEquals( $expected, $actual );

        // We're rewriting WP to Destination URL correctly (with trailing slash)
        $expected = 'https://bar.com/';
        $actual = SimpleRewriter::rewriteFileContents( 'https://foo.com/' );
        $this->assertEquals( $expected, $actual );

        // Multiple URLs are being rewritten
        $expected = 'multiple https://bar.com occurances https://bar.com present';
        $actual = SimpleRewriter::rewriteFileContents(
            'multiple https://foo.com occurances https://foo.com present'
        );
        $this->assertEquals( $expected, $actual );

        // URLs with params are being rewritten
        $expected = 'https://bar.com/bar/baz';
        $actual = SimpleRewriter::rewriteFileContents( 'https://foo.com/bar/baz' );
        $this->assertEquals( $expected, $actual );

        // @todo URLs are not being cleaned correctly. Is this OK?
        $expected = 'https://bar.com//bar/baz';
        $actual = SimpleRewriter::rewriteFileContents( 'https://foo.com//bar/baz' );
        $this->assertEquals( $expected, $actual );

        // Protocol relative URLs are being rewritten
        $expected = '//bar.com/bar/baz';
        $actual = SimpleRewriter::rewriteFileContents( '//foo.com/bar/baz' );
        $this->assertEquals( $expected, $actual );

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
