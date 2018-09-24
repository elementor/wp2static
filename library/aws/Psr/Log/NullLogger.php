<?php
/**
 * NullLogger
 *
 * @package WP2Static
 */

namespace Psr\Log;
class NullLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
    }
}
