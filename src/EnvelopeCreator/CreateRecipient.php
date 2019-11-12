<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Utils\CallbackRouteGenerator;
use Symfony\Component\Routing\RouterInterface;

final class CreateRecipient implements EnvelopeBuilderCallableInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function __invoke(EnvelopeBuilderInterface $envelopeBuilder, array $context = []): string
    {
        $recipientViewRequest = new Model\RecipientViewRequest([
            'authentication_method' => EnvelopeBuilder::EMBEDDED_AUTHENTICATION_METHOD,
            'client_user_id' => $envelopeBuilder->getAccountId(),
            'recipient_id' => '1',
            'return_url' => CallbackRouteGenerator::getCallbackRoute($this->router, $envelopeBuilder),
            'user_name' => $envelopeBuilder->getSignerName(),
            'email' => $envelopeBuilder->getSignerEmail(),
        ]);

        return $envelopeBuilder->getEnvelopesApi()->createRecipientView($envelopeBuilder->getAccountId(), $envelopeBuilder->getEnvelopeId(), $recipientViewRequest)->getUrl();
    }
}
