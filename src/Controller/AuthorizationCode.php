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

namespace DocusignBundle\Controller;

use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Events\AuthorizationCodeEvent;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AuthorizationCode
{
    private $tokenEncoder;
    private $envelopeBuilder;

    public function __construct(TokenEncoderInterface $tokenEncoder, EnvelopeBuilderInterface $envelopeBuilder)
    {
        $this->tokenEncoder = $tokenEncoder;
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        if (!$this->tokenEncoder->isTokenValid([], $request->query->get('state'))) {
            throw new AccessDeniedHttpException();
        }

        if (empty($request->query->get('code'))) {
            throw new MissingMandatoryParameterHttpException();
        }

        $event = new AuthorizationCodeEvent($this->envelopeBuilder, $request, new Response('', Response::HTTP_ACCEPTED));

        $eventDispatcher->dispatch($event);

        return $event->getResponse();
    }
}
