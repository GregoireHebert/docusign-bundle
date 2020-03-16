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

namespace DocusignBundle\EventSubscriber;

use DocusignBundle\AuthorizationCodeHandler\AuthorizationCodeHandlerInterface;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Events\AuthorizationCodeEvent;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthorizationCodeEventSubscriber implements EventSubscriberInterface
{
    public const DEMO_ACCOUNT_API_URI = 'https://account-d.docusign.com';
    public const ACCOUNT_API_URI = 'https://account.docusign.com';

    private $authorizationCodeHandler;
    private $tokenEncoder;
    private $router;
    private $integrationKey;
    private $secret;
    private $accountApiUri;
    private $client;

    public function __construct(
        AuthorizationCodeHandlerInterface $authorizationCodeHandler,
        TokenEncoderInterface $tokenEncoder,
        RouterInterface $router,
        string $integrationKey,
        string $secret,
        bool $demo,
        HttpClientInterface $client = null
    ) {
        $this->authorizationCodeHandler = $authorizationCodeHandler;
        $this->tokenEncoder = $tokenEncoder;
        $this->router = $router;
        $this->integrationKey = $integrationKey;
        $this->secret = $secret;
        $this->accountApiUri = $demo ? self::DEMO_ACCOUNT_API_URI : self::ACCOUNT_API_URI;
        $this->client = $client ?: HttpClient::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreSignEvent::class => ['onPreSign', 10],
            AuthorizationCodeEvent::class => ['onAuthorizationCode', 10],
        ];
    }

    public function onPreSign(PreSignEvent $event): void
    {
        // If auth mode is not valid, or no code is available: redirect to DocuSign to get a code
        if (EnvelopeBuilder::AUTH_MODE_CODE !== $event->getEnvelopeBuilder()->getAuthMode()
            || !empty($this->authorizationCodeHandler->read(['signature_name' => $event->getEnvelopeBuilder()->getName()]))
        ) {
            return;
        }

        $event->setResponse(new RedirectResponse(sprintf(
            '%s?response_type=code&scope=signature&client_id=%s&state=%s&redirect_uri=%s',
            "$this->accountApiUri/oauth/auth",
            $this->integrationKey,
            $this->tokenEncoder->encode([]),
            $this->router->generate('docusign_authorization_code_'.$event->getEnvelopeBuilder()->getName(), [], RouterInterface::ABSOLUTE_URL)
        ), RedirectResponse::HTTP_TEMPORARY_REDIRECT));
    }

    public function onAuthorizationCode(AuthorizationCodeEvent $event): void
    {
        // AuthorizationCode is valid for 30 seconds: get the access_token directly to prevent expiration
        $code = $event->getRequest()->query->get('code');
        $authorization = base64_encode("$this->integrationKey:$this->secret");
        $response = $this->client->request('POST', "$this->accountApiUri/oauth/token", [
            'headers' => [
                'Authorization' => "Basic $authorization",
            ],
            'body' => "grant_type=authorization_code&code=$code",
        ]);

        if (!empty($accessToken = $response->toArray()['access_token'])) {
            $this->authorizationCodeHandler->write(
                $accessToken,
                ['signature_name' => $event->getEnvelopeBuilder()->getName()]
            );
        }
    }
}
