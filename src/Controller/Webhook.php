<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\Events\WebhookEventFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Webhook
{
    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger): Response
    {
        $data = simplexml_load_string($request->getContent());

        $status = $data->EnvelopeStatus->Status->__toString();
        $logger->info('DocuSign Webhook called.', ['status' => $status]);

        $event = WebhookEventFactory::create($status, $data, $request);
        $eventDispatcher->dispatch(\get_class($event), $event);

        return new Response('', 202);
    }
}
