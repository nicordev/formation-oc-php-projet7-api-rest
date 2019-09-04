<?php

namespace App\EventListener;


use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $headers = $response->headers;
        $cacheControlHeader = $headers->get("Cache-Control");
        $headerParts = explode(", ", $cacheControlHeader);

        foreach ($headerParts as &$headerPart) {
            if ($headerPart === "no-cache") {
                unset($headerPart);
            }
        }

        $headers->remove("Cache-Control");
        $headers->set("Cache-Control", $headerParts);
        $response->headers = $headers;
    }
}