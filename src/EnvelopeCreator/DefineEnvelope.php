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
use DocusignBundle\DocusignBundle;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Translator\TranslatorAware;
use DocusignBundle\Translator\TranslatorTrait;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class DefineEnvelope implements EnvelopeBuilderCallableInterface, TranslatorAware
{
    public const EMAIL_SUBJECT = 'Please sign this document';
    public const WEBHOOK_ROUTE_NAME = 'docusign_webhook';

    use TranslatorTrait;

    private $router;
    private $envelopeBuilder;
    private $translator;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->envelopeBuilder = $envelopeBuilder;
        $this->translator = $translator;
    }

    public function __invoke(array $context = []): void
    {
        if ($context['signature_name'] !== $this->envelopeBuilder->getName()) {
            return;
        }

        $this->envelopeBuilder->setEnvelopeDefinition(new Model\EnvelopeDefinition([
            'email_subject' => $this->translator->trans(self::EMAIL_SUBJECT, [], DocusignBundle::TRANSLATION_DOMAIN),
            'documents' => [$this->envelopeBuilder->getDocument()],
            'recipients' => new Model\Recipients(['signers' => $this->envelopeBuilder->getSigners(), 'carbon_copies' => $this->envelopeBuilder->getCarbonCopies() ?? null]),
            'status' => 'sent',
            'event_notification' => $this->getEventsNotifications(),
        ]));
    }

    private function getEventsNotifications(): Model\EventNotification
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
        $eventNotification->setUrl($this->router->generate(self::WEBHOOK_ROUTE_NAME, $this->envelopeBuilder->getWebhookParameters(), Router::ABSOLUTE_URL));
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
