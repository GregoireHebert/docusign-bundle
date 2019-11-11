<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilder;
use InvalidArgumentException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class CreateRecipient
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(EnvelopeBuilder $envelopeBuilder): Model\ViewUrl
    {
        $recipientViewRequest = new Model\RecipientViewRequest([
            'authentication_method' => EnvelopeBuilder::EMBEDDED_AUTHENTICATION_METHOD,
            'client_user_id' => $envelopeBuilder->accountId,
            'recipient_id' => '1',
            'return_url' => $this->getCallbackRoute($envelopeBuilder),
            'user_name' => $envelopeBuilder->signerName,
            'email' => $envelopeBuilder->signerEmail,
        ]);

        return $envelopeBuilder->envelopesApi->createRecipientView($envelopeBuilder->accountId, $envelopeBuilder->envelopeId, $recipientViewRequest);
    }

    /**
     * Returns the callback.
     */
    private function getCallbackRoute(EnvelopeBuilder $envelopeBuilder): string
    {
        try {
            Assert::regex($envelopeBuilder->callbackRouteName, '#^https?://.+(:[0-9]+)?$#');

            return $envelopeBuilder->callbackRouteName;
        } catch (InvalidArgumentException $exception) {
            return $this->router->generate($envelopeBuilder->callbackRouteName, array_unique(['envelopeId' => $envelopeBuilder->envelopeId] + $envelopeBuilder->callbackParameters), Router::ABSOLUTE_URL);
        }
    }
}
