<?php

namespace App\Middlewares;

use Core\Contracts\CacheInterface;
use Core\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseCacheMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $ttl;

    public function __construct(CacheInterface $cache, int $ttl = 60)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = $this->getCacheKey($request);

        if ($this->cache->has($key)) {
            $cachedContent = $this->cache->get($key);
            return new Response($cachedContent);
        }

        $response = $next($request);

        $contentType = $request->headers->get('Content-Type');
        $accept = $request->headers->get('Accept');

        if (
            $response->getStatusCode() === 200
            && isset($accept)
            && (
                $request->isXmlHttpRequest()
                || (str_contains($accept, 'application/json'))
                || ($contentType === 'application/json')
            )
        ) {
            $this->cache->set($key, $response->getContent(), $this->ttl);
        }

        return $response;
    }

    private function getCacheKey(Request $request): string
    {
        return 'response_cache_' . md5($request->getMethod() . '_' . $request->getRequestUri());
    }
}