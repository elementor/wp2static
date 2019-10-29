<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;

final class StaticSiteTest extends TestCase{

    public function testCreatesDirectoryOnConstruction() {
        $temp_directory = "/tmp/testCreatesDirectoryOnConstruction" .
            microtime( true );

        $static_site  = new StaticSite( $temp_directory );

        $this->assertSame(
            true,
            is_dir( $temp_directory )
        );
    }

    public function testAddsFileContentsToPathInsideStaticSite() {
        $temp_directory = "/tmp/testAddsFileContentsToPathInsideStaticSite" .
            microtime( true );

        $path = 'a_directory/a_file.txt';

        $contents = 'Quick lick foxes';

        $static_site  = new StaticSite( $temp_directory );

        $static_site->add( 'a_directory/a_file.txt', $contents );

        $this->assertSame(
            true,
            is_dir( dirname( "$static_site->path/$path" ) )
        );

        $this->assertEquals(
            $contents,
            file_get_contents( "$static_site->path/$path" )
        );
    }
}
