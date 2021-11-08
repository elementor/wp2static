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
final class FileHelperTest extends TestCase {

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
    public function testDeleteDirWithFiles() {
        // Set up a virual folder structure
        $structure = [
            // Latin characters
            'top_level_latin_folder' => [
                'no_file_extension' => 'no_file_extension',
                // phpcs:ignore Generic.Files.LineLength
                'example_of_an_extremely_long_latin_file_name_with_some_numbers_at_the_end_0123456789.fileextension' => 'example_of_an_extremely_long_latin_file_name_with_some_numbers_at_the_end_0123456789.fileextension',
            ],
            // UTF-8 characters
            'top_level_ùnicodë_folder' => [
                'unicodÉ-file.jpg' => 'unicodÉ-file.jpg',
                'sêcond_level_fÒlder' => [
                    'ÚÑÌÇÕÐË.pdf' => 'ÚÑÌÇÕÐË.pdf',
                    'second-unicøde-file.sql' => 'second-unicøde-file.sql',

                ],
            ],
            // Spaces
            'top level folder with spaces' => [
                'only a subfolder' => [
                    'example file.php' => 'example file.php',
                ],
            ],
        ];
        $vfs = vfsStream::setup( 'root' );
        vfsStream::create( $structure, $vfs );

        // Check vfsStream set up the top level directories correctly.
        // We don't *really* need to do this as it should be covered in
        // vfsStream's tests but it gives peace of mind and will confirm
        // our below tests are actually doing something.
        foreach ( array_keys( $structure ) as $folder ) {
            $filepath = vfsStream::url( "root/$folder" );
            $expected = true;
            $actual = is_dir( $filepath );
            $this->assertEquals( $expected, $actual );
        }

        // Delete the unicode subfolder
        $filepath = vfsStream::url( 'root/top_level_ùnicodë_folder/sêcond_level_fÒlder' );
        FilesHelper::deleteDirWithFiles( $filepath );
        // Confirm it's gone
        $expected = false;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );
        // And confirm its parent still exists
        $filepath = vfsStream::url( 'root/top_level_ùnicodë_folder' );
        $expected = true;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );

        // Delete a subfolder with spaces in the filename
        $filepath = vfsStream::url( 'root/top level folder with spaces/only a subfolder' );
        FilesHelper::deleteDirWithFiles( $filepath );
        // Confirm it's gone
        $expected = false;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );
        // And confirm its parent still exists
        $filepath = vfsStream::url( 'root/top level folder with spaces' );
        $expected = true;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test getListOfLocalFilesByDir method
     *
     * @return void
     */
    public function testGetListOfLocalFilesByDir() {
        $filenames_to_ignore = CoreOptions::getDefaultLineDelimitedBlobValue(
            'filenamesToIgnore'
        );
        $file_extensions_to_ignore = CoreOptions::getDefaultLineDelimitedBlobValue(
            'fileExtensionsToIgnore'
        );

        // Set up a virtual folder structure
        $structure = [
            // Latin characters
            'top_level_latin_folder' => [
                'no_file_extension' => 'no_file_extension',
                // phpcs:ignore Generic.Files.LineLength
                'example_of_an_extremely_long_latin_file_name_with_some_numbers_at_the_end_0123456789.fileextension' => 'example_of_an_extremely_long_latin_file_name_with_some_numbers_at_the_end_0123456789.fileextension',
            ],
            // UTF-8 characters
            'top_level_ùnicodë_folder' => [
                'unicodÉ-file.jpg' => 'unicodÉ-file.jpg',
                'sêcond_level_fÒlder' => [
                    'ÚÑÌÇÕÐË.pdf' => 'ÚÑÌÇÕÐË.pdf',
                    'second-unicøde-file.php' => 'second-unicøde-file.php',

                ],
            ],
            // Spaces
            'top level folder with spaces' => [
                'only a subfolder' => [
                    'example file.pdf' => 'example file.pdf',
                ],
            ],
        ];
        $vfs = vfsStream::setup( 'root' );
        vfsStream::create( $structure, $vfs );
        // Set virtual WP root directory to /root/ for this test
        $mock = Mockery::mock( 'overload:\WP2Static\SiteInfo' );
        $mock->shouldreceive( 'getPath' )->andReturn( vfsStream::url( 'root' ) . '/' );

        // Top level folder
        $filepath = vfsStream::url( 'root/top_level_latin_folder' );
        $expected = [
            '/top_level_latin_folder/no_file_extension',
            // phpcs:ignore Generic.Files.LineLength
            '/top_level_latin_folder/example_of_an_extremely_long_latin_file_name_with_some_numbers_at_the_end_0123456789.fileextension',
        ];
        $actual = FilesHelper::getListOfLocalFilesByDir(
            $filepath,
            $filenames_to_ignore,
            $file_extensions_to_ignore
        );
        $this->assertEquals( $expected, $actual );

        // Nested folder
        // This is actually two tests in one. One of the files in this folder
        // has a 'php' extension which is disallowed and shouldn't be returned.
        $filepath = vfsStream::url( 'root/top_level_ùnicodë_folder/sêcond_level_fÒlder' );
        $expected = [
            '/top_level_%C3%B9nicod%C3%AB_folder/s%C3%AAcond_level_f%C3%92lder/ÚÑÌÇÕÐË.pdf',
        ];
        $actual = FilesHelper::getListOfLocalFilesByDir(
            $filepath,
            $filenames_to_ignore,
            $file_extensions_to_ignore
        );
        $this->assertEquals( $expected, $actual );

        // Folder with subfolder
        $filepath = vfsStream::url( 'root/top level folder with spaces' );
        $expected = [
            '/top%20level%20folder%20with%20spaces/only a subfolder/example file.pdf',
        ];
        $actual = FilesHelper::getListOfLocalFilesByDir(
            $filepath,
            $filenames_to_ignore,
            $file_extensions_to_ignore
        );

        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test pathLooksCrawlable method
     *
     * @return void
     */
    public function testPathLooksCrawlable() {
        $filenames_to_ignore = CoreOptions::getDefaultLineDelimitedBlobValue(
            'filenamesToIgnore'
        );
        $file_extensions_to_ignore = CoreOptions::getDefaultLineDelimitedBlobValue(
            'fileExtensionsToIgnore'
        );

        $looks_crawlable = function( $file_name ) use (
            &$filenames_to_ignore,
            &$file_extensions_to_ignore
        ) {
            return FilesHelper::pathLooksCrawlable(
                $file_name,
                $filenames_to_ignore,
                $file_extensions_to_ignore
            );
        };
        // Default accepted extension
        $expected = true;
        $actual = $looks_crawlable( '/path/to/foo.jpg' );
        $this->assertEquals( $expected, $actual );

        // Default disallowed extension
        $expected = false;
        $actual = $looks_crawlable( '/path/to/foo.txt' );
        $this->assertEquals( $expected, $actual );

        // Default disallowed extension uppercase
        $expected = false;
        $actual = $looks_crawlable( '/path/to/FOO.TXT' );
        $this->assertEquals( $expected, $actual );

        // Default disallowed extension with . replaced with any other character
        // This is to test bad regex
        $expected = true;
        $actual = $looks_crawlable( '/path/to/foohtxt' );
        $this->assertEquals( $expected, $actual );

        // Default disallowed filename
        $expected = false;
        $actual = $looks_crawlable( '/path/to/thumbs.db' );
        $this->assertEquals( $expected, $actual );

        // Try a disallowed URL - .git filepaths
        $expected = false;
        $actual = $looks_crawlable(
            // @phpcs:ignore Generic.Files.LineLength.TooLong
            'http://foo.com/wp-content/plugins/my-plugin/.git/objects/0b/f00ad2a21d59fc587a605008d3c3a83bb81e51'
        );
        $this->assertEquals( $expected, $actual );

        // Try a disallowed URL - .git filepaths uppercase
        $expected = false;
        $actual = $looks_crawlable(
            // @phpcs:ignore Generic.Files.LineLength.TooLong
            'http://foo.com/wp-content/plugins/my-plugin/.GIT/objects/0b/f00ad2a21d59fc587a605008d3c3a83bb81e51'
        );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test pathLooksCrawlable method's $file_extensions_to_ignore argument
     * filter.
     *
     * @return void
     */
    public function testPathLooksCrawlableExtension() {
        $looks_crawlable = function( $file_name ) {
            return FilesHelper::pathLooksCrawlable(
                $file_name,
                [],
                [ '.unknown' ]
            );
        };

        // We've disallowed .unknown - test it
        $expected = false;
        $actual = $looks_crawlable( '/path/to/foo.unknown' );
        $this->assertEquals( $expected, $actual );

        // txt extensions should now be allowed - test it
        $expected = true;
        $actual = $looks_crawlable( '/path/to/foo.txt' );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test pathLooksCrawlable method's $filenames_to_ignore argument
     * filter.
     *
     * @return void
     */
    public function testPathLooksCrawlableFilenames() {
        $looks_crawlable = function( $file_name ) {
            return FilesHelper::pathLooksCrawlable(
                $file_name,
                [ 'yarn.lock' ],
                []
            );
        };

        // We've disallowed yarn.lock - test it
        $expected = false;
        $actual = $looks_crawlable( '/path/to/yarn.lock' );
        $this->assertEquals( $expected, $actual );

        // thumbs.db filenames should now be allowed - test it
        $expected = true;
        $actual = $looks_crawlable( '/path/to/thumbs.db' );
        $this->assertEquals( $expected, $actual );
    }

    public function testCleanDetectedURLs() {
        // Mock the WP functions used by FilesHelper::cleanDetectedURLs()
        $mock = \Mockery::mock( 'alias:WP2Static\SiteInfo' )
            ->shouldReceive( 'getUrl' )
            ->andReturn( 'https://foo.com/' );

        // No trailing slash
        $expected = [ '/foo' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com/foo' ] );
        $this->assertEquals( $expected, $actual );

        // Trailing slash
        $expected = [ '/foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com/foo/' ] );
        $this->assertEquals( $expected, $actual );

        // Double trailing slash
        $expected = [ '/foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com/foo//' ] );
        $this->assertEquals( $expected, $actual );

        // Double middle slash
        $expected = [ '/foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com//foo/' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - no trailing slash
        $expected = [ 'foo' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'foo' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - trailing slash
        $expected = [ 'foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'foo/' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - starting + trailing slash
        $expected = [ '/foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ '/foo/' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - double trailing slash
        $expected = [ 'foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'foo//' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - double starting slash
        $expected = [ '/foo' ];
        $actual = FilesHelper::cleanDetectedURLs( [ '//foo' ] );
        $this->assertEquals( $expected, $actual );

        // Single URL param - double starting + trailing slash
        $expected = [ '/foo/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ '//foo//' ] );
        $this->assertEquals( $expected, $actual );

        // Two URL params, Trailing slash
        $expected = [ '/foo/bar/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com/foo/bar/' ] );
        $this->assertEquals( $expected, $actual );

        // Two URL params, Double middle slash
        $expected = [ '/foo/bar/' ];
        $actual = FilesHelper::cleanDetectedURLs( [ 'https://foo.com/foo//bar/' ] );
        $this->assertEquals( $expected, $actual );
    }
}
