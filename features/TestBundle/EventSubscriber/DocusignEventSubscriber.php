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

namespace DocusignBundle\E2e\TestBundle\EventSubscriber;

use DocusignBundle\Events\AuthorizationCodeEvent;
use DocusignBundle\Events\CompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class DocusignEventSubscriber implements EventSubscriberInterface
{
    private $router;
    private $session;

    public function __construct(RouterInterface $router, Session $session)
    {
        $this->router = $router;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuthorizationCodeEvent::class => 'onAuthorizationCode',
            CompletedEvent::class => 'onDocumentSigned',
        ];
    }

    public function onAuthorizationCode(AuthorizationCodeEvent $event): void
    {
        $this->session->getFlashBag()->add('success', 'Authorization has been granted! You can now sign the document.');
        $event->setResponse(new RedirectResponse($this->router->generate($event->getEnvelopeBuilder()->getName())));
    }

    public function onDocumentSigned(CompletedEvent $event): void
    {
        $document = $event->getData()->DocumentPDFs->DocumentPDF[0];
        file_put_contents('completed.pdf', base64_decode($document->PDFBytes->__toString(), true));
    }
}
