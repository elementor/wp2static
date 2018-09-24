<?php
/**
 * MessageFactory
 *
 * @package WP2Static
 */

namespace Http\Message;

/**
 * Factory for PSR-7 Request and Response.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface MessageFactory extends RequestFactory, ResponseFactory
{
}
