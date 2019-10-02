<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\Events\DocumentSignedEvent;
use DocusignBundle\Events\WebHookEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Webhook
{
    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        $eventDispatcher->dispatch(WebHookEvent::DOCUMENT_SIGNED, new DocumentSignedEvent($request->getContent()));

        return new Response('', 202);
    }
}
