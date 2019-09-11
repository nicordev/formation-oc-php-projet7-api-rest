<?php

namespace App\EventListener;


use App\Helper\Cache\CacheKeyGenerator;
use App\Helper\Cache\CacheTool;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Routing\RouterInterface;

class HttpCacheListener
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var CacheTool
     */
    private $cacheTool;
    /**
     * @var CacheKeyGenerator
     */
    private $keyGenerator;
    /**
     * @var string
     */
    private $cacheItemKey;
    /**
     * @var array
     */
    private $privateRoutes;
    /**
     * @var array
     */
    private $routesToCache;
    /**
     * @var bool
     */
    private $canBeCached = true;
    /**
     * @var void
     */
    private $tag;

    public function __construct(
        RouterInterface $router,
        CacheTool $cacheTool,
        CacheKeyGenerator $keyGenerator,
        string $projectDirectory
    ) {
        $this->router = $router;
        $this->cacheTool = $cacheTool;
        $this->keyGenerator = $keyGenerator;
        $this->privateRoutes = require "$projectDirectory/config/http_cache/private_routes.php";
        $this->routesToCache = require "$projectDirectory/config/http_cache/routes_to_cache.php";
    }

    /**
     * Generate a key and try to return a matching item from the cache
     *
     * @param RequestEvent $event
     * @return bool
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $this->cacheItemKey = $this->keyGenerator->generateKeyFromRequest(
            $request,
            $this->privateRoutes,
            $route
        );
        $this->tag = $this->cacheTool->generateTag($route);

        if (!in_array($route, $this->routesToCache)) {
            $this->canBeCached = false;
            return;
        }

        $cachedItem = $this->cacheTool->getItemFromCache($this->cacheItemKey);

        if ($cachedItem) {
            $cachedResponse = $cachedItem->get();
            $this->canBeCached = false;
            return $cachedResponse;
        }
    }

    /**
     * Save the response in the cache
     *
     * @param ResponseEvent $event
     * @throws \Exception
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if ($this->canBeCached) {
            $response = $event->getResponse();

            $this->cacheTool->saveResponseInCache(
                $this->cacheItemKey ?? "no_key",
                $response,
                $this->tag ? [$this->tag] : null
            );
        }
    }
}