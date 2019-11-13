<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        return $router->generate($envelopeBuilder->getCallback(), $queryParameters, Router::ABSOLUTE_URL);
    }
}
