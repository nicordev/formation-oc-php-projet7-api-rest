<?php

namespace App\EventListener;


use App\Annotation\CacheTool;
use App\Helper\AnnotationReadingTool\AnnotationReadingTool;
use App\Helper\CacheKeyGenerator\CacheKeyGenerator;
use App\Helper\Cache\Cache;
use App\Helper\HeaderGenerator;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Routing\RouterInterface;

class HttpCacheListener
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var CacheKeyGenerator
     */
    private $keyGenerator;
    /**
     * @var string
     */
    private $cacheItemKey;
    /**
     * @var bool
     */
    private $canBeCachedRegardingToRequest = true;
    /**
     * @var string
     */
    private $tag;
    /**
     * @var string
     */
    private $requestedRoute;
    /**
     * @var AnnotationReadingTool
     */
    private $annotationReadingTool;
    /**
     * @var CacheTool
     */
    private $cacheToolAnnotation;

    public const CACHE_EXPIRATION = "+10 minutes";

    public function __construct(
        RouterInterface $router,
        Cache $cache,
        CacheKeyGenerator $keyGenerator,
        AnnotationReadingTool $annotationReadingTool
    ) {
        $this->router = $router;
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator;
        $this->annotationReadingTool = $annotationReadingTool;
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
            $this->requestedRoute
        );
        $this->tag = $this->cache->generateTag($this->requestedRoute);

        if (!$this->cache->canBeCached($this->requestedRoute)) {
            $this->canBeCachedRegardingToRequest = false;

            return;
        }

        $cachedItem = $this->cache->getItemFromCache($this->cacheItemKey);

        if ($cachedItem) {
            $cachedResponse = $cachedItem->get();
            $this->canBeCachedRegardingToRequest = false;
            $event->setResponse($cachedResponse);

            return $cachedResponse;
        }
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controllerAndMethod = $event->getController();
        $this->cacheToolAnnotation = $this->annotationReadingTool->getMethodAnnotation(
            CacheTool::class,
            get_class($controllerAndMethod[0]),
            $controllerAndMethod[1]
        );
    }

    /**
     * Add headers and invalidate tags
     *
     * @param ViewEvent $event
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function onKernelView(ViewEvent $event)
    {
        $view = $event->getControllerResult();
        $data = $view->getData();

        // Generate headers
        if ($this->canBeCachedRegardingToRequest) {
            if (strpos($this->requestedRoute, "show") !== false) {
                $headers = HeaderGenerator::generateShowHeaders(
                    self::CACHE_EXPIRATION,
                    $data
                );
            } elseif (strpos($this->requestedRoute, "list") !== false) {
                $entity = explode("_", $this->requestedRoute)[0];
                $headers = HeaderGenerator::generateListHeaders(
                    self::CACHE_EXPIRATION,
                    $entity
                );
            }

            if (isset($headers)) {
                $view->setHeaders($headers);
            };
        }

        // Invalidate tags
        if (!empty($this->cacheToolAnnotation->tagsToInvalidate)) {
            $this->cache->invalidateTags($this->cacheToolAnnotation->tagsToInvalidate);
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
        if ($this->canBeCachedRegardingToRequest) {
            $response = $event->getResponse();

            if ($this->cache->canBeCachedRegardingToCacheControlHeader($response)) {
                $this->cache->saveResponseInCache(
                    $this->cacheItemKey ?? "no_key",
                    $response,
                    $this->tag ? [$this->tag] : null
                );
            }
        }
    }
}