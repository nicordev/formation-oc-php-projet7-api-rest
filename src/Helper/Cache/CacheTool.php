<?php

namespace App\Helper\Cache;


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
     * Add some cache relative headers to the response
     *
     * @param Response $response
     * @param \DateTimeInterface|null $expires
     * @param string|null $etag
     * @param \DateTimeInterface|null $lastModified
     * @param bool $public
     */
    public function configureResponse(
        Response $response,
        ?\DateTimeInterface $expires = null,
        ?string $etag = null,
        ?\DateTimeInterface $lastModified = null,
        bool $public = true
    ) {
        if ($expires) {
            $response->setExpires($expires);
        }
        if ($etag) {
            $response->setEtag($etag);
        }
        if ($lastModified) {
            $response->setLastModified($lastModified);
        }
        if ($public) {
            $response->setPublic();
        }
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
}