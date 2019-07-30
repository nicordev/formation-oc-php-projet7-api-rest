<?php

namespace App\EventListener;


use App\Exception\ResourceValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ResourceValidationException) {
            $response = new Response(
                \json_encode($exception->getMessage()),
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => "application/json"
                ]
            );

            $event->setResponse($response);
        }
    }
}