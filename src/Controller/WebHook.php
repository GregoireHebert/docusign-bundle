<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\Events\DocumentSignedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="docusign_webhook", methods={"post"}, path="docusign/webhook")
 */
final class WebHook
{
    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        $eventDispatcher->dispatch(new DocumentSignedEvent($request));

        return new Response('', 202);
    }
}
