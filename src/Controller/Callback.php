<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="docusign_callback", methods={"get"}, path="docusign/callback")
 */
final class Callback
{
    private const EVENT_COMPLETE = 'signing_complete';

    public function __invoke(Request $request): Response
    {
        if (self::EVENT_COMPLETE !== $status = $request->get('event')) {
            return new Response("The document signature ended with an unexpected $status status.");
        }

        return new Response('Congratulation! Document signed.');
    }
}
