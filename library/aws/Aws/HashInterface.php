<?php
/**
 * HashInterface
 *
 * @package WP2Static
 */

namespace Aws;
interface HashInterface
{
    public function update($data);
    public function complete();
    public function reset();
}
