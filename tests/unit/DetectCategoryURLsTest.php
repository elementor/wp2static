<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectCategoryURLsTest extends TestCase {


    public function testDetect() {
        $site_url = 'https://foo.com/';
        $taxonomies = [
            (object) [ 'name' => 'category' ],
            (object) [ 'name' => 'post_tag' ],
        ];
        $terms = [
            'category' => [
                'category1' => (object) [
                    'name' => 'category1',
                    'count' => 1,
                ],
                'category2' => (object) [
                    'name' => 'category2',
                    'count' => 3,
                ],
                'category3' => (object) [
                    'name' => 'category3',
                    'count' => 4,
                ],
            ],
            'post_tag' => [
                'post_tag1' => (object) [
                    'name' => 'post_tag1',
                    'count' => 14,
                ],
            ],
        ];
        $term_links = [
            'category1' => "{$site_url}category/1",
            'category2' => "{$site_url}category/2",
            'category3' => "{$site_url}category/3",
            'post_tag1' => "{$site_url}tags/foo/bar",
        ];

        // Set pagination to 3 posts per page
        \WP_Mock::userFunction(
            'get_option',
            [
                'times' => 1,
                'args' => [ 'posts_per_page' ],
                'return' => 3,
            ]
        );
        // Set up our custom taxonomies
        \WP_Mock::userFunction(
            'get_taxonomies',
            [
                'times' => 1,
                'args' => [
                    [ 'public' => true ],
                    'objects',
                ],
                'return' => $taxonomies,
            ]
        );
        foreach ( $taxonomies as $taxonomy ) {
            // And the terms within those taxonomies
            \WP_Mock::userFunction(
                'get_terms',
                [
                    'times' => 1,
                    'args' => [
                        $taxonomy->name,
                        [ 'hide_empty' => true ],
                    ],
                    'return' => $terms[ $taxonomy->name ],
                ]
            );

            // ...and the links for those terms
            foreach ( $terms[ $taxonomy->name ] as $term ) {
                \WP_Mock::userFunction(
                    'get_term_link',
                    [
                        'times' => 1,
                        'args' => [ $term ],
                        'return' => $term_links[ $term->name ],
                    ]
                );
            }
        }

        $expected = [
            "{$site_url}category/1",
            "{$site_url}category/2",
            "{$site_url}category/3",
            "{$site_url}tags/foo/bar",
        ];
        $actual = DetectCategoryURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
