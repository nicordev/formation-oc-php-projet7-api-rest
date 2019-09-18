<?php

namespace App\Helper\Cache;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class Cache
{
    /**
     * @var TagAwareCacheInterface
     */
    public $cache;

    public function __construct(TagAwareCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get an item from the cache
     *
     * @param string $itemKey
     * @return bool
     */
    public function getItemFromCache(string $itemKey)
    {
        $item = $this->cache->getItem($itemKey);

        if ($item->isHit()) {
            return $item;
        }

        return false;
    }

    /**
     * Save the response in the cache
     *
     * @param string $itemKey
     * @param Response $response
     * @param array $tags |null
     */
    public function saveResponseInCache(
        string $itemKey,
        Response $response,
        ?array $tags = null
    ) {
        $cachedResponse = $this->cache->getItem($itemKey);
        $cachedResponse->set($response);

        if ($expires = $response->getExpires()) {
            $cachedResponse->expiresAt($expires);
        }

        if ($tags) {
            $cachedResponse->tag($tags);
        }

        $this->cache->save($cachedResponse);
    }

    /**
     * Invalidate cache items using their tags
     *
     * @param array $tags
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function invalidateTags(array $tags)
    {
        $this->cache->invalidateTags($tags);
    }

    /**
     * Generate a tag from the first two parts of the route name
     *
     * @param string $route
     * @return string
     */
    public function generateTag(string $route)
    {
        $routeParts = explode("_", $route);

        if (count($routeParts) > 1) {
            return $routeParts[0] . "_" . $routeParts[1];
        }

        return $route;
    }

    /**
     * Look in the Cache-Control header to see if the response can be cached
     *
     * @param Response $response
     * @return bool
     */
    public function canBeCachedRegardingToCacheControlHeader(Response $response)
    {
        $cacheControl = $response->headers->get("cache-control");

        if (strpos($cacheControl, "no-cache") === false || strpos($cacheControl, "private") === false) {
            return true;
        }

        return false;
    }
}