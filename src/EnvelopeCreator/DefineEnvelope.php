<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class DefineEnvelope
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(EnvelopeBuilder $envelopeBuilder): void
    {
        $envelopeBuilder->envelopeDefinition = new Model\EnvelopeDefinition([
            'email_subject' => EnvelopeBuilder::EMAIL_SUBJECT,
            'documents' => [$envelopeBuilder->document],
            'recipients' => new Model\Recipients(['signers' => $envelopeBuilder->signers, 'carbon_copies' => $envelopeBuilder->carbonCopies ?? null]),
            'status' => 'sent',
            'event_notification' => $this->getEventsNotifications($envelopeBuilder),
        ]);
    }

    private function getEventsNotifications(EnvelopeBuilder $envelopeBuilder): Model\EventNotification
    {
        $envelopeEvents = [
            (new Model\EnvelopeEvent())->setEnvelopeEventStatusCode('sent'),
            (new Model\EnvelopeEvent())->setEnvelopeEventStatusCode('delivered'),
            (new Model\EnvelopeEvent())->setEnvelopeEventStatusCode('completed'),
            (new Model\EnvelopeEvent())->setEnvelopeEventStatusCode('declined'),
            (new Model\EnvelopeEvent())->setEnvelopeEventStatusCode('voided'),
        ];

        $recipientEvents = [
            (new Model\RecipientEvent())->setRecipientEventStatusCode('Sent'),
            (new Model\RecipientEvent())->setRecipientEventStatusCode('Delivered'),
            (new Model\RecipientEvent())->setRecipientEventStatusCode('Completed'),
            (new Model\RecipientEvent())->setRecipientEventStatusCode('Declined'),
            (new Model\RecipientEvent())->setRecipientEventStatusCode('AuthenticationFailed'),
            (new Model\RecipientEvent())->setRecipientEventStatusCode('AutoResponded'),
        ];

        $eventNotification = new Model\EventNotification();
        $eventNotification->setUrl($this->router->generate($envelopeBuilder->webhookRouteName, $envelopeBuilder->webhookParameters, Router::ABSOLUTE_URL));
        $eventNotification->setLoggingEnabled('true');
        $eventNotification->setRequireAcknowledgment('true');
        $eventNotification->setUseSoapInterface('false');
        $eventNotification->setIncludeCertificateWithSoap('false');
        $eventNotification->setSignMessageWithX509Cert('false');
        $eventNotification->setIncludeDocuments('true');
        $eventNotification->setIncludeEnvelopeVoidReason('true');
        $eventNotification->setIncludeTimeZone('true');
        $eventNotification->setIncludeSenderAccountAsCustomField('true');
        $eventNotification->setIncludeDocumentFields('true');
        $eventNotification->setIncludeCertificateOfCompletion('true');
        $eventNotification->setEnvelopeEvents($envelopeEvents);
        $eventNotification->setRecipientEvents($recipientEvents);

        return $eventNotification;
    }
}
