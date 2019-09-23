<?php

declare(strict_types=1);

namespace DocusignBundle\Controller;

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Utils\SignatureExtractor;
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
    public function __invoke(EnvelopeBuilder $envelopeBuilder, SignatureExtractor $signatureExtractor, Request $request, LoggerInterface $logger): Response
    {
        if (null === $path = $request->get('path')) {
            throw new MissingMandatoryParametersException('You must define a `path` query parameter.');
        }

        try {
            $envelopeBuilder = $envelopeBuilder->setFile($path);
            $signatures = $signatureExtractor->getSignatures();

            if (empty($signatures)) {
                throw new \LogicException('No signatures defined. Check your `signatures` configuration and query parameter.');
            }

            foreach ($signatures as $signature) {
                $envelopeBuilder->addSignatureZone($signature['page'], $signature['xPosition'], $signature['yPosition']);
            }

            return new RedirectResponse($envelopeBuilder->createEnvelope(), 307);
        } catch (FileNotFoundException $exception) {
            $logger->notice('document to sign not found', ['message' => $exception->getMessage()]);

            throw new NotFoundHttpException();
        }
    }
}
