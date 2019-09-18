<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Response;

class CacheController
{
    /**
     * @var Response
     */
    private $cachedResponse;

    public function __construct(Response $cachedResponse)
    {
        $this->cachedResponse = $cachedResponse;
    }

    public function sendResponse()
    {
        return $this->cachedResponse;
    }
}