<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\EnvelopeBuilder;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * @Route(name="docusign", methods={"post", "get"}, path="docusign")
 */
final class Sign
{
    public function __invoke(EnvelopeBuilder $envelopeBuilder, Request $request, LoggerInterface $logger): Response
    {
        if (null === $path = $request->get('path')) {
            throw new MissingMandatoryParametersException('You must define a `path` parameter.');
        }

        try {
            $url = $envelopeBuilder
                ->setFile($path)
                ->addSignatureZone(1, 350, 675)
                ->createEnvelope();

            return new RedirectResponse($url, 307);
        } catch (FileNotFoundException $exception) {
            $logger->notice('document to sign not found', ['message' => $exception->getMessage()]);

            throw new NotFoundHttpException();
        }
    }
}
