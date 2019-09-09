<?php

namespace App\Helper;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheTool
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
    public function getContentFromCache(string $itemKey)
    {
        $item = $this->cache->getItem($itemKey);

        if ($item->isHit()) {
            return $item->get();
        }

        return false;
    }

    /**
     * Save the response in the cache
     *
     * @param string $itemKey
     * @param Response $response
     * @param array $tags |null
     * @param \DateTimeInterface|null $expires
     * @param bool $public
     */
    public function saveResponseInCache(
        string $itemKey,
        Response $response,
        ?array $tags = null,
        ?\DateTimeInterface $expires = null,
        bool $public = true
    ) {
        if ($public) {
            $response->setPublic();
        }
        if ($expires) {
            $response->setExpires($expires);
        }
        $cachedResponse = $this->cache->getItem($itemKey);
        $cachedResponse->set($response);
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