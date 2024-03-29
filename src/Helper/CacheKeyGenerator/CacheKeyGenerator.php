<?php

namespace App\Helper\CacheKeyGenerator;


use App\Helper\Cache\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class CacheKeyGenerator
{
    /**
     * @var RouterInterface
     */
    private $router;

    public const KEY_PARTS_INNER_SEPARATOR = '.';
    public const KEY_PARTS_OUTER_SEPARATOR = '|';

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Generate a key from a request and can fill route and jwt variables
     *
     * @param Request $request
     * @param bool $isPrivate
     * @param string|null $route
     * @param string|null $jwt
     * @return string
     */
    public function generateKeyFromRequest(
        Request $request,
        bool $isPrivate = false,
        ?string &$route = null,
        ?string &$jwt = null
    ) {
        $routeParts = $this->extractRoutePartsFromRequest($request);
        $parameters = $this->extractParametersFromRequest($request);
        $route = $routeParts["_route"];
        $keyParts[] = implode(self::KEY_PARTS_INNER_SEPARATOR, $routeParts);
        $keyParts[] = implode(self::KEY_PARTS_INNER_SEPARATOR, $parameters);

        if ($isPrivate) {
            $jwt = $this->extractUserTokenFromRequest($request);
            $keyParts[] = $jwt;
        }

        return implode(self::KEY_PARTS_OUTER_SEPARATOR, $keyParts);
    }

    private function extractRoutePartsFromRequest(Request $request)
    {
        $routeData = $this->router->matchRequest($request);
        $routeData["_controller"] = str_replace("\\", '~', $routeData["_controller"]);
        $routeData["_controller"] = str_replace("::", '~', $routeData["_controller"]);

        return $routeData;
    }

    private function extractParametersFromRequest(Request $request)
    {
        $parameters = $request->query->all();
        ksort($parameters);

        return $parameters;
    }

    private function extractUserTokenFromRequest(Request $request)
    {
        return $request->headers->get("authorization");
    }
}
