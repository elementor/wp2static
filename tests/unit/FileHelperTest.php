<?php

namespace WP2Static;

use Mockery;
use org\bovigo\vfs\vfsStream;
use WP_Mock;
use WP_Mock\Tools\TestCase;

final class FileHelperTest extends TestCase {
    public function setUp() : void {
        WP_Mock::setUp();
    }

    public function tearDown() : void {
        WP_Mock::tearDown();
    }

    /**
     * Test delete_dir_with_files method
     *
     * @return void
     */
    public function testDeleteDirWithFiles() {
        // Set up a virual folder structure
        $structure = [
            'folder_1' => [
                'file_1.txt' => 'file_1.txt',
                'file_2.txt' => 'file_2.txt',
            ],
            'folder_2' => [
                'file_3.txt' => 'file_3.txt',
                'file_4.txt' => 'file_4.txt',
                'folder_3' => [
                    'file_5.txt' => 'file_5.txt',
                    'file_6.txt' => 'file_6.txt',
                    
                ],
            ],
        ];
        $vfs = vfsStream::setup('root');
        vfsStream::create($structure, $vfs);
        $filepath = vfsStream::url('root/folder_1');

        // Check vfsStream set up the directories correctly
        $expected = true;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );

        // folder_1 should now be gone
        FilesHelper::delete_dir_with_files($filepath);
        $expected = false;
        $actual = is_dir( $filepath );
        $this->assertEquals( $expected, $actual );

        // Delete a nested folder
        $filepath = vfsStream::url('root/folder_2/folder_3');
        FilesHelper::delete_dir_with_files($filepath);
        // And confirm the top level one still exists
        $filepath = vfsStream::url('root/folder_2');
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
        // Set up a virual folder structure
        $structure = [
            'folder_1' => [
                'file_1.jpg' => 'file_1.jpg',
                'file_2.jpg' => 'file_2.jpg',
            ],
            'folder_2' => [
                'file_3.jpg' => 'file_3.jpg',
                'file_4.jpg' => 'file_4.jpg',
                'folder_3' => [
                    'file_5.jpg' => 'file_5.jpg',
                    'file_6.jpg' => 'file_6.jpg',
                    
                ],
            ],
        ];
        $vfs = vfsStream::setup('root');
        vfsStream::create($structure, $vfs);
        // Set virtual WP root directory to /root/ for this test
        $mock = Mockery::mock('overload:\WP2Static\SiteInfo');
        $mock->shouldreceive('getPath')->andReturn(vfsStream::url('root') . "/");


        // Top level folder
        $filepath = vfsStream::url('root/folder_1');
        $expected = ['/folder_1/file_1.jpg', '/folder_1/file_2.jpg'];
        $actual = FilesHelper::getListOfLocalFilesByDir( $filepath );
        $this->assertEquals( $expected, $actual );

        // Nested folder
        $filepath = vfsStream::url('root/folder_2/folder_3');
        $expected = ['/folder_2/folder_3/file_5.jpg', '/folder_2/folder_3/file_6.jpg'];
        $actual = FilesHelper::getListOfLocalFilesByDir( $filepath );
        $this->assertEquals( $expected, $actual );

        // Folder with subfolder
        $filepath = vfsStream::url('root/folder_2');
        $expected = [
            '/folder_2/file_3.jpg',
            '/folder_2/file_4.jpg',
            '/folder_2/folder_3/file_5.jpg',
            '/folder_2/folder_3/file_6.jpg'
        ];
        $actual = FilesHelper::getListOfLocalFilesByDir( $filepath );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test filePathLooksCrawlable method
     *
     * @return void
     */
    public function testFilePathLooksCrawlable() {
        // Default accepted extension
        $expected = true;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/foo.jpg" );
        $this->assertEquals( $expected, $actual );

        // Default disallowed extension
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/foo.txt" );
        $this->assertEquals( $expected, $actual );

        // Default disallowed filename
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/thumbs.db" );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test filePathLooksCrawlable method's wp2static_file_extensions_to_ignore
     * filter.
     *
     * @return void
     */
    public function testFilePathLooksCrawlableExtensionFilter() {
        // txt extensions should be disallowed
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/foo.txt" );
        $this->assertEquals( $expected, $actual );

        // Here we're changing which fil eextensions are no longer allowed.
        \WP_Mock::onFilter( 'wp2static_file_extensions_to_ignore' )
            ->with([
                '.bat',
                '.crt',
                '.DS_Store',
                '.git',
                '.idea',
                '.ini',
                '.less',
                '.map',
                '.md',
                '.mo',
                '.php',
                '.PHP',
                '.phtml',
                '.po',
                '.pot',
                '.scss',
                '.sh',
                '.sql',
                '.SQL',
                '.tar.gz',
                '.tpl',
                '.txt',
                '.yarn',
                '.zip',
            ])
            ->reply(['.unknown']);
        // We've disallowed .unknown - test it
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/foo.unknown" );
        $this->assertEquals( $expected, $actual );

        // txt extensions should now be allowed - test it
        $expected = true;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/foo.txt" );
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test filePathLooksCrawlable method's wp2static_filenames_to_ignore
     * filter.
     *
     * @return void
     */
    public function testFilePathLooksCrawlableFilenameFilter() {
        // thumbs.db filenames are currently disallowed
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/thumbs.db" );
        $this->assertEquals( $expected, $actual );

        // Here we're changing which fil eextensions are no longer allowed.
        \WP_Mock::onFilter( 'wp2static_filenames_to_ignore' )
            ->with([
                '__MACOSX',
                '.babelrc',
                '.gitignore',
                '.gitkeep',
                '.htaccess',
                '.php',
                '.travis.yml',
                'backwpup',
                'bower_components',
                'bower.json',
                'composer.json',
                'composer.lock',
                'config.rb',
                'current-export',
                'Dockerfile',
                'gulpfile.js',
                'latest-export',
                'LICENSE',
                'Makefile',
                'node_modules',
                'package.json',
                'pb_backupbuddy',
                'plugins/wp2static',
                'previous-export',
                'README',
                'static-html-output-plugin',
                'thumbs.db',
                'tinymce',
                'wc-logs',
                'wpallexport',
                'wpallimport',
                'wp-static-html-output', // exclude earlier version exports
                'wp2static-addon',
                'wp2static-crawled-site',
                'wp2static-processed-site',
                'wp2static-working-files',
                'yarn-error.log',
                'yarn.lock',
            ])
            ->reply(['yarn.lock']);
        // We've disallowed yarn.lock - test it
        $expected = false;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/yarn.lock" );
        $this->assertEquals( $expected, $actual );

        // thumbs.db filenames should now be allowed - test it
        $expected = true;
        $actual = FilesHelper::filePathLooksCrawlable( "/path/to/thumbs.db" );
        $this->assertEquals( $expected, $actual );
    }

    public function testCleanDetectedURLs() {
        // Mock the WP functions used by FilesHelper::cleanDetectedURLs()
        $mock = \Mockery::mock('alias:WP2Static\SiteInfo')
            ->shouldReceive('getUrl')
            ->andReturn('https://foo.com/');

        // No trailing slash
        $expected = ['/foo'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com/foo"]);
        $this->assertEquals( $expected, $actual );

        // Trailing slash
        $expected = ['/foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com/foo/"]);
        $this->assertEquals( $expected, $actual );

        // Double trailing slash
        $expected = ['/foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com/foo//"]);
        $this->assertEquals( $expected, $actual );

        // Double middle slash
        $expected = ['/foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com//foo/"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - no trailing slash
        $expected = ['foo'];
        $actual = FilesHelper::cleanDetectedURLs(["foo"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - trailing slash
        $expected = ['foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["foo/"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - starting + trailing slash
        $expected = ['/foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["/foo/"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - double trailing slash
        $expected = ['foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["foo//"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - double starting slash
        $expected = ['/foo'];
        $actual = FilesHelper::cleanDetectedURLs(["//foo"]);
        $this->assertEquals( $expected, $actual );

        // Single URL param - double starting + trailing slash
        $expected = ['/foo/'];
        $actual = FilesHelper::cleanDetectedURLs(["//foo//"]);
        $this->assertEquals( $expected, $actual );

        // Two URL params, Trailing slash
        $expected = ['/foo/bar/'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com/foo/bar/"]);
        $this->assertEquals( $expected, $actual );

        // Two URL params, Double middle slash
        $expected = ['/foo/bar/'];
        $actual = FilesHelper::cleanDetectedURLs(["https://foo.com/foo//bar/"]);
        $this->assertEquals( $expected, $actual );
    }
}
