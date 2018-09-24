<?php
/**
 * Functions include
 *
 * @package WP2Static
 */

// Don't redefine the functions if included multiple times.
if (!function_exists('GuzzleHttp\Psr7\str')) {
    require __DIR__ . '/functions.php';
}
