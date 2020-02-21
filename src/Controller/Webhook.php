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

use DocusignBundle\Events\WebhookEventFactory;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Webhook
{
    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, TokenEncoderInterface $tokenEncoder): Response
    {
        if (!$tokenEncoder->isTokenValid($request->query->all(), $request->query->get('_token'))) {
            throw new AccessDeniedHttpException();
        }

        $data = simplexml_load_string($request->getContent());

        $status = $data->EnvelopeStatus->Status->__toString();
        $logger->info('DocuSign Webhook called.', ['status' => $status]);

        $eventDispatcher->dispatch(WebhookEventFactory::create($status, $data, $request));

        return new Response('', 202);
    }
}
