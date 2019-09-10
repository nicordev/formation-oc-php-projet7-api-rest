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
    private $requestKey;
    /**
     * @var string
     */
    private $requestRoute;

    public function __construct(
        RouterInterface $router,
        CacheTool $cacheTool,
        CacheKeyGenerator $keyGenerator
    ) {
        $this->router = $router;
        $this->cacheTool = $cacheTool;
        $this->keyGenerator = $keyGenerator;
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
        $this->requestKey = $this->keyGenerator->generateKey($request);
        $cachedResponse = $this->cacheTool->getContentFromCache($this->requestKey);

        if ($cachedResponse) {
            $event->stopPropagation();

            return $cachedResponse;
        }
    }

    /**
     * Save the response in the cache
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $this->cacheTool->saveResponseInCache(
            $this->requestKey,
            $response,

        );
    }
}