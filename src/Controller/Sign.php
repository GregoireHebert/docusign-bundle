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

use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\Exception\FileNotFoundException;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Sign
{
    private $envelopeBuilder;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder)
    {
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger): Response
    {
        if (empty($path = $request->query->get('path'))) {
            throw new MissingMandatoryParameterHttpException('You must define a `path` query parameter.');
        }

        if (!empty($signerName = $request->query->get('signerName'))) {
            $this->envelopeBuilder->setSignerName($signerName);
        }

        if (!empty($signerEmail = $request->query->get('signerEmail'))) {
            $this->envelopeBuilder->setSignerEmail($signerEmail);
        }

        try {
            $eventDispatcher->dispatch($preSignEvent = new PreSignEvent($this->envelopeBuilder, $request));
            if (null !== $response = $preSignEvent->getResponse()) {
                return $response;
            }

            $this->envelopeBuilder->setFile($path);

            return new RedirectResponse($this->envelopeBuilder->createEnvelope(), 307);
        } catch (FileNotFoundException $exception) {
            $logger->error('Document to sign not found.', ['message' => $exception->getMessage()]);

            throw new NotFoundHttpException();
        }
    }
}
