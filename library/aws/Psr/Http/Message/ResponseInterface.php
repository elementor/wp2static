<?php
/**
 * ResponseInterface
 *
 * @package WP2Static
 */

namespace Psr\Http\Message;
interface ResponseInterface extends MessageInterface
{
    public function getStatusCode();
    public function withStatus($code, $reasonPhrase = '');
    public function getReasonPhrase();
}
