<?php

declare(strict_types=1);

namespace DocusignBundle\Utils;

use DocusignBundle\EnvelopeBuilderInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CallbackRouteGenerator
{
    public static function getCallbackRoute(RouterInterface $router, EnvelopeBuilderInterface $envelopeBuilder): string
    {
        $queryParameters = array_unique(['envelopeId' => $envelopeBuilder->getEnvelopeId()] + $envelopeBuilder->getCallbackParameters());

        // Callback is a route name
        if (!preg_match('/^https?:\/\//', $envelopeBuilder->getCallback())) {
            return $router->generate($envelopeBuilder->getCallback(), $queryParameters, Router::ABSOLUTE_URL);
        }

        // Callback is already an url
        return $envelopeBuilder->getCallback().(parse_url($envelopeBuilder->getCallback(), PHP_URL_QUERY) ? '&' : '?').http_build_query($queryParameters);
    }
}
