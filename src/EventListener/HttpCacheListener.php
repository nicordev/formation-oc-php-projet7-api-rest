<?php

namespace App\EventListener;


use App\Annotation\CacheTool;
use App\Controller\CacheController;
use App\Helper\AnnotationReadingTool\AnnotationReadingTool;
use App\Helper\CacheKeyGenerator\CacheKeyGenerator;
use App\Helper\Cache\Cache;
use App\Helper\CacheTagMaker\CacheTagMaker;
use App\Helper\HeaderGenerator;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
    private $isCacheable = true;
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
    /**
     * @var CacheTagMaker
     */
    private $cacheTagMaker;

    public function __construct(
        RouterInterface $router,
        Cache $cache,
        CacheKeyGenerator $keyGenerator,
        AnnotationReadingTool $annotationReadingTool,
        CacheTagMaker $cacheTagMaker
    ) {
        $this->router = $router;
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator;
        $this->annotationReadingTool = $annotationReadingTool;
        $this->cacheTagMaker = $cacheTagMaker;
    }

    /**
     * Invalidate tags, generate a key and try to return a matching item from the cache
     *
     * @param ControllerEvent $event
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function onKernelController(ControllerEvent $event)
    {
        $controllerAndMethod = $event->getController();

        if (!is_array($controllerAndMethod) && count($controllerAndMethod) < 2) {
            return;
        }

        $this->cacheToolAnnotation = $this->annotationReadingTool->getMethodAnnotation(
            CacheTool::class,
            get_class($controllerAndMethod[0]),
            $controllerAndMethod[1]
        );

        if (!$this->cacheToolAnnotation) {
            return;
        }

        $request = $event->getRequest();

        foreach ($this->cacheToolAnnotation->tags as &$tag) {
            $this->cacheTagMaker->addIdForShowAction($request, $tag);
        }

        foreach ($this->cacheToolAnnotation->tagsToInvalidate as &$tag) {
            $this->cacheTagMaker->addIdForShowAction($request, $tag);
        }

        if (!empty($this->cacheToolAnnotation->tagsToInvalidate)) {
            $this->cache->invalidateTags($this->cacheToolAnnotation->tagsToInvalidate);
        }

        if (!$this->cacheToolAnnotation->isCacheable) {
            $this->isCacheable = false;
            return;
        }

        $this->cacheItemKey = $this->keyGenerator->generateKeyFromRequest(
            $request,
            $this->cacheToolAnnotation->isPrivate,
            $this->requestedRoute
        );
        $cachedItem = $this->cache->getItemFromCache($this->cacheItemKey);

        if ($cachedItem) {
            $cachedResponse = $cachedItem->get();
            $this->isCacheable = false;
            $cacheController = new CacheController($cachedResponse);
            $event->setController([$cacheController, "sendResponse"]);
        }
    }

    /**
     * Add headers
     *
     * @param ViewEvent $event
     * @throws \Exception
     */
    public function onKernelView(ViewEvent $event)
    {
        if ($this->isCacheable) {
            $view = $event->getControllerResult();
            $data = $view->getData();

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
            }
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
        if ($this->isCacheable) {
            $response = $event->getResponse();

            if ($this->cache->canBeCachedRegardingToCacheControlHeader($response)) {
                $this->cache->saveResponseInCache(
                    $this->cacheItemKey ?? "no_key",
                    $response,
                    $this->cacheToolAnnotation->tags
                );
            }
        }
    }
}
