<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\Events\DocumentSignatureCompleted;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="docusign_callback", methods={"get"}, path="docusign/callback/{envelopeId}")
 */
final class Callback
{
    public const EVENT_COMPLETE = 'signing_complete';

    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        if (self::EVENT_COMPLETE !== $status = $request->get('event')) {
            return new Response("The document signature ended with an unexpected $status status.");
        }

        $eventDispatcher->dispatch(new DocumentSignatureCompleted($request->get('envelopeId')));

        return new Response('Congratulation! Document signed.');
    }
}
