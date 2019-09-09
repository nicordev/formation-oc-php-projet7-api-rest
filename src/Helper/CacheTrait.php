<?php

namespace App\Helper;


use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

trait CacheTrait
{
    /**
     * Get an item from the cache
     *
     * @param TagAwareCacheInterface $cache
     * @param string $itemKey
     * @return bool
     */
    public function getContentFromCache(TagAwareCacheInterface $cache, string $itemKey)
    {
        $item = $cache->getItem($itemKey);

        if ($item->isHit()) {
            return $item->get();
        }

        return false;
    }

    public function saveResponseInCache(
        TagAwareCacheInterface $cache,
        string $itemKey,
        Response $response,
        array $tags = [],
        bool $public = true
    ) {
        if ($public) {
            $response->setPublic();
        }
        $cachedResponse = $cache->getItem($itemKey);
        $cachedResponse->set($response);
        $cachedResponse->tag($tags);
        $cache->save($cachedResponse);
    }

    /**
     * Create a key string to use with cache items
     *
     * @param array $parts
     * @param string $glue
     * @param string $prefix
     * @return string
     */
    public function makeItemKey(array $parts, string $glue = "", string $prefix = "")
    {
        $keyParts = array_merge([$prefix], $parts);
        return implode($glue, $keyParts);
    }
}