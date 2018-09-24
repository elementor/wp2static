<?php
/**
 * CommandInterface
 *
 * @package WP2Static
 */

namespace Aws;
interface CommandInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
    public function toArray();
    public function getName();
    public function hasParam($name);
    public function getHandlerList();
}
