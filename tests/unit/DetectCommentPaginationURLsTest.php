<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectCommentPaginationURLsTest extends TestCase {


    public function testDetect() {
        $site_url = 'https://foo.com/';
        $comments = [
            (object)['comment_ID' => 1],
            (object)['comment_ID' => 2],
            (object)['comment_ID' => 3],
            (object)['comment_ID' => 4],
        ];
        $comment_links = [
            // 2 comments on the same post
            1 => "{$site_url}?p=1#comment-1",
            2 => "{$site_url}?p=1#comment-2",
            3 => "{$site_url}2020/10/foo#comment-3",
            4 => "{$site_url}2020/10/bar#comment-4",
        ];

        // Create our list of custom comments
        \WP_Mock::userFunction(
            'get_comments',
            [
                'times' => 1,
                'args' => [],
                'return' => $comments,
            ]
        );
        // And the links for them
        foreach ( $comment_links as $comment_id => $comment_link ) {
            \WP_Mock::userFunction(
                'get_comment_link',
                [
                    'times' => 1,
                    'args' => [ $comment_id ],
                    'return' => $comment_link,
                ]
            );
        }

        // Duplicate p=1 URL removed
        // Array keys are preserved in the output even though we don't use them.
        // Hahstags have been stripped
        $expected = [
            0 => "{$site_url}?p=1",
            2 => "{$site_url}2020/10/foo",
            3 => "{$site_url}2020/10/bar",
        ];
        $actual = DetectCommentPaginationURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
