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

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilderInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

final class DefineEnvelope implements EnvelopeBuilderCallableInterface
{
    public const EMAIL_SUBJECT = 'Please sign this document';

    private $router;
    private $webhookRouteName;

    public function __construct(RouterInterface $router, string $webhookRouteName)
    {
        $this->router = $router;
        $this->webhookRouteName = $webhookRouteName;
    }

    public function __invoke(EnvelopeBuilderInterface $envelopeBuilder, array $context = []): void
    {
        $envelopeBuilder->setEnvelopeDefinition(new Model\EnvelopeDefinition([
            'email_subject' => self::EMAIL_SUBJECT,
            'documents' => [$envelopeBuilder->getDocument()],
            'recipients' => new Model\Recipients(['signers' => $envelopeBuilder->getSigners(), 'carbon_copies' => $envelopeBuilder->getCarbonCopies() ?? null]),
            'status' => 'sent',
            'event_notification' => $this->getEventsNotifications($envelopeBuilder),
        ]));
    }

    private function getEventsNotifications(EnvelopeBuilderInterface $envelopeBuilder): Model\EventNotification
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
        $eventNotification->setUrl($this->router->generate($this->webhookRouteName, $envelopeBuilder->getWebhookParameters(), Router::ABSOLUTE_URL));
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
