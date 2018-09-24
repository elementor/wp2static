<?php
/**
 * Search function
 *
 * @package WP2Static
 */

namespace JmesPath;
if (!function_exists(__NAMESPACE__ . '\search')) {
    function search($expression, $data)
    {
        return Env::search($expression, $data);
    }
}
