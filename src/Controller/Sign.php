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

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Sign
{
    public function __invoke(EnvelopeBuilder $envelopeBuilder, SignatureExtractor $signatureExtractor, Request $request, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger): Response
    {
        if (empty($path = $request->query->get('path'))) {
            throw new MissingMandatoryParameterHttpException('You must define a `path` query parameter.');
        }

        try {
            $eventDispatcher->dispatch(PreSignEvent::class, new PreSignEvent($envelopeBuilder, $request));

            $envelopeBuilder->setFile($path);
            $signatures = $signatureExtractor->getSignatures();

            if (empty($signatures)) {
                throw new \LogicException('No signatures defined. Check your `signatures` configuration and query parameter.');
            }

            foreach ($signatures as $signature) {
                $envelopeBuilder->addSignatureZone($signature['page'], $signature['x_position'], $signature['y_position']);
            }

            return new RedirectResponse($envelopeBuilder->createEnvelope(), 307);
        } catch (FileNotFoundException $exception) {
            $logger->error('Document to sign not found.', ['message' => $exception->getMessage()]);

            throw new NotFoundHttpException();
        }
    }
}
