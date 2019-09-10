<?php

namespace App\EventListener;


use App\Helper\Cache\CacheKeyGenerator;
use App\Helper\Cache\CacheTool;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
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
            $this->privateRoutes
        );
        $cachedResponse = $this->cacheTool->getContentFromCache($this->cacheItemKey);

        if ($cachedResponse) {
            $event->stopPropagation();

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
        $response = $event->getResponse();

        $this->cacheTool->configureResponse(
            $response,
            new \DateTime("+1 hour"),
            null,
            new \DateTime(),
            true
        );

        $this->cacheTool->saveResponseInCache(
            $this->cacheItemKey,
            $response
        );
    }
}