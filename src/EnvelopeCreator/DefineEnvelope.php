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
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Translator\TranslatorAwareTrait;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

final class DefineEnvelope implements EnvelopeBuilderCallableInterface, TranslatorAwareInterface
{
    public const EMAIL_SUBJECT = 'Please sign this document';

    use TranslatorAwareTrait;

    private $router;
    private $envelopeBuilder;
    private $tokenEncoder;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router, TokenEncoderInterface $tokenEncoder)
    {
        $this->router = $router;
        $this->envelopeBuilder = $envelopeBuilder;
        $this->tokenEncoder = $tokenEncoder;
    }

    public function __invoke(array $context = []): void
    {
        if ($context['signature_name'] !== $this->envelopeBuilder->getName()) {
            return;
        }

        $this->envelopeBuilder->setEnvelopeDefinition(new Model\EnvelopeDefinition([
            'email_subject' => $this->getTranslator()->trans(self::EMAIL_SUBJECT, [], DocusignBundle::TRANSLATION_DOMAIN),
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

        // Add WebHook security parameter
        $this->envelopeBuilder->addWebhookParameter('_token', $this->tokenEncoder->encode($this->envelopeBuilder->getWebhookParameters()));

        $eventNotification = new Model\EventNotification();
        $eventNotification->setUrl($this->router->generate('docusign_webhook_'.$this->envelopeBuilder->getName(), $this->envelopeBuilder->getWebhookParameters(), Router::ABSOLUTE_URL));
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
