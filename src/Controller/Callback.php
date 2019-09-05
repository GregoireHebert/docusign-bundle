<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\Exception\BadStatusException;
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
            throw new BadStatusException("The document signature ended with an unexpected $status status.");
        }

        return new Response('Congratulation! Document signed.');
        // alright what do we do now, from here I have no trace of which document I was signing...
        // maybe have a look at the https://developers.docusign.com/esign-rest-api/code-examples/webhook-status
        // working url to test {WEBSITE}/admin/docusign?path=http://10.206.16.7/sites/CorumOrigin/ACT_LOT3/SUPPLIERS/00000021_FRA/Interventions%20Statements/SO000064.pdf
    }
}
