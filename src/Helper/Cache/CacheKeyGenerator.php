<?php

namespace App\Helper\Cache;


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
     * @param array $privateRoutes
     * @param string|null $route
     * @param string|null $jwt
     * @return string
     */
    public function generateKeyFromRequest(
        Request $request,
        array $privateRoutes = [],
        ?string &$route = null,
        ?string &$jwt = null
    ) {
        $routeParts = $this->extractRoutePartsFromRequest($request);
        $parameters = $this->extractParametersFromRequest($request);
        $route = $routeParts["_route"];
        $keyParts[] = implode(self::KEY_PARTS_INNER_SEPARATOR, $routeParts);
        $keyParts[] = implode(self::KEY_PARTS_INNER_SEPARATOR, $parameters);

        if (in_array($routeParts["_route"], $privateRoutes)) {
            $jwt = $this->extractUserTokenFromRequest($request);
            $keyParts[] = $jwt;
        }

        $key = implode(self::KEY_PARTS_OUTER_SEPARATOR, $keyParts);

        return $key;
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