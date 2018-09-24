<?php
/**
 * LoggerAwareInterface
 *
 * @package WP2Static
 */

namespace Psr\Log;
interface LoggerAwareInterface
{
    public function setLogger(LoggerInterface $logger);
}
