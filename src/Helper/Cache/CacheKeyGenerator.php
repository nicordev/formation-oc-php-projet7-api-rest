<?php

namespace App\Helper\Cache;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

class CacheKeyGenerator
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function generateKeyFromRequest(Request $request)
    {
        $routeData = $this->router->matchRequest($request);
        $key = "zog";

        return $key;
    }

    public function mergeKeyElements(
        array $parts,
        string $glue = "",
        string $prefix = ""
    ) {
        $keyParts = array_merge([$prefix], $parts);
        return implode($glue, $keyParts);
    }
}