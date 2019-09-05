<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="docusign", methods={"post", "get"}, path="docusign")
 */
final class Sign
{
    public function __invoke(EnvelopeBuilder $envelopeBuilder, Request $request): Response
    {
        if (null === $path = $request->get('path')) {
            throw new NotFoundHttpException();
        }

        $url = $envelopeBuilder
            ->setFile($path)
            ->addSignatureZone(1, 350, 675)
            ->createEnvelope();

        return new RedirectResponse($url, 307);
    }
}
