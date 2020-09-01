<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectAttachmentURLsTest extends TestCase {


    public function testDetect() {
        global $wpdb;
        $site_url = 'https://foo.com/';

        // Create 3 attachments
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wpdb = Mockery::mock( '\WPDB' );
        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [ 1, 2, 3 ] );
        $wpdb->posts = 'wp_posts';

        // And URLs for them
        for ( $i = 1; $i <= 3; $i++ ) {
            \WP_Mock::userFunction(
                'get_attachment_link',
                [
                    'times' => 1,
                    'args' => [ $i ],
                    'return' => "{$site_url}attachment-{$i}/",
                ]
            );
        }

        $expected = [
            "{$site_url}attachment-1/",
            "{$site_url}attachment-2/",
            "{$site_url}attachment-3/",
        ];
        $actual = DetectAttachmentURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
