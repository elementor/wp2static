<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Prepend a base path to the request URI. Useful for base API URLs like http://domain.com/api.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AddPathPlugin implements Plugin
{
    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @param UriInterface $uri
     */
    public function __construct(UriInterface $uri)
    {
        if ('' === $uri->getPath()) {
            throw new \LogicException('URI path cannot be empty');
        }

        if ('/' === substr($uri->getPath(), -1)) {
            throw new \LogicException('URI path cannot end with a slash.');
        }

        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $request = $request->withUri($request->getUri()
            ->withPath($this->uri->getPath().$request->getUri()->getPath())
        );

        return $next($request);
    }
}
