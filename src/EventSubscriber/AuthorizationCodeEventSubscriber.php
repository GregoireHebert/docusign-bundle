<?php

declare(strict_types=1);

namespace DocusignBundle\EventSubscriber;

use DocusignBundle\AuthorizationCodeHandler\AuthorizationCodeHandlerInterface;
use DocusignBundle\Events\PreSignEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthorizationCodeEventSubscriber implements EventSubscriberInterface
{
    private $authorizationCodeHandler;

    public function __construct(AuthorizationCodeHandlerInterface $authorizationCodeHandler)
    {
        $this->authorizationCodeHandler = $authorizationCodeHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [PreSignEvent::class => ['onPreSignEvent', 10]];
    }

    public function onPreSignEvent(PreSignEvent $preSignEvent): void
    {
        // if authorization_code auth mode and no code is available redirect to docusign to get a code
        if (empty($this->authorizationCodeHandler->read()) && null === $code = $preSignEvent->getRequest()->query->get('code')) {
            // redirect to docusign with current url as callback
            $preSignEvent->setResponse(new RedirectResponse('', 307));
            return;
        }

        if (!empty($code)) {
            $this->authorizationCodeHandler->write($code);
        }
    }

}
