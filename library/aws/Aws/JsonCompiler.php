<?php
/**
 * JsonCompiler
 *
 * @package WP2Static
 */

namespace Aws;
class JsonCompiler
{
    const CACHE_ENV = 'AWS_PHP_CACHE_DIR';
    public function load($path)
    {
        return load_compiled_json($path);
    }
}
