<?php

declare(strict_types=1);

namespace DocusignBundle;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\ApiException;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model;
use DocusignBundle\Exception\UnableToSignException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class EnvelopeBuilder
{
    public const EMBEDDED_AUTHENTICATION_METHOD = 'NONE';
    public const EMAIL_SUBJECT = 'Please sign this document';

    /** @var string */
    private $accessToken;
    /** @var string */
    private $accountId;
    /** @var string */
    private $signerName;
    /** @var string */
    private $signerEmail;
    /** @var Model\Signer[]|array */
    private $signers = [];
    /** @var string */
    private $apiURI;
    /** @var string|null */
    private $filePath;
    /** @var Model\SignHere[]|array */
    private $signatureZones = [];
    /** @var int */
    private $docReference;
    /** @var int */
    private $signatureNo = 1;
    /** @var FilesystemInterface */
    private $fileSystem;
    /** @var Model\Document|null */
    private $document;
    /** @var Model\EnvelopeDefinition|null */
    private $envelopeDefinition;
    /** @var Configuration */
    private $config;
    /** @var LoggerInterface */
    private $logger;
    /** @var ApiClient|null */
    private $apiClient;
    /** @var EnvelopesApi|null */
    private $envelopesApi;
    /** @var string|null */
    private $envelopeId;
    /** @var string */
    private $callBackRouteName;
    /** @var string */
    private $webHookRouteName;
    /** @var RouterInterface */
    private $router;
    /** @var Model\CarbonCopy[]|array */
    private $carbonCopies = [];

    public function __construct(LoggerInterface $logger, RouterInterface $router, FilesystemInterface $docusignStorage, string $accessToken, string $accountId, string $defaultSignerName, string $defaultSignerEmail, string $apiURI, string $callBackRouteName, string $webHookRouteName)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->fileSystem = $docusignStorage;
        $this->accessToken = $accessToken;
        $this->accountId = $accountId;

        $this->signerName = $defaultSignerName;
        $this->signerEmail = $defaultSignerEmail;

        $this->apiURI = $apiURI;
        $this->callBackRouteName = $callBackRouteName;
        $this->webHookRouteName = $webHookRouteName;

        $this->docReference = time();
    }

    public function setFile(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    protected function getEventsNotifications(): Model\EventNotification
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
        $eventNotification->setUrl($this->router->generate($this->webHookRouteName, [], Router::ABSOLUTE_URL));
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

    /*
     * Add a carbon copy to receive the notifications from docusign.
     */
    public function addCarbonCopy(string $name, string $email): self
    {
        Assert::email($email);

        $carbonCopy = new Model\CarbonCopy();
        $carbonCopy->setEmail($email);
        $carbonCopy->setName($name);
        $carbonCopy->setRecipientId((string) $this->docReference);

        $this->carbonCopies[] = $carbonCopy;

        return $this;
    }

    public function addSignatureZone(int $pageNumber, int $xPosition, int $yPosition): self
    {
        $this->signatureZones[] = new Model\SignHere([
            'document_id' => $this->docReference,
            'page_number' => $pageNumber,
            'recipient_id' => $this->signatureNo,
            'tab_label' => "Signature {$this->signatureNo}",
            'x_position' => $xPosition,
            'y_position' => $yPosition,
        ]);

        return $this;
    }

    /**
     * @throws FileNotFoundException
     *
     * @return string path to redirect
     *
     * @throw InvalidArgumentException
     */
    public function createEnvelope(): string
    {
        try {
            $this->validate();
            $this->createDocument();
            $this->addSigner($this->signerName, $this->signerEmail);
            $this->defineEnvelope();
            $this->sendEnvelope();
            $viewUrl = $this->createRecipient();

            return $viewUrl->getUrl();
        } catch (ApiException $exception) {
            $this->logger->critical('Impossible to send a document to docusign', [
                'document' => $this->document,
                'signers' => $this->signers,
                'envelope' => $this->envelopeDefinition,
                'request' => $exception->getResponseBody(),
            ]);

            throw new UnableToSignException($exception->getMessage());
        } finally {
            $this->reset();
        }
    }

    private function validate(): void
    {
        Assert::notNull($this->filePath);
        Assert::notEmpty($this->docReference);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createDocument(): void
    {
        ['extension' => $extension, 'filename' => $filename] = pathinfo($this->filePath);
        $contentBytes = $this->fileSystem->read($this->filePath);
        $base64FileContent = base64_encode($contentBytes);
        $this->document = new Model\Document([
            'document_base64' => $base64FileContent,
            'name' => $filename,
            'file_extension' => $extension,
            'document_id' => $this->docReference,
        ]);
    }

    /*
     * set an additional signer to the document.
     */
    public function addSigner(string $name, string $email): self
    {
        if (empty($this->signatureZones)) {
            throw new \LogicException('You must create the signature zones before');
        }

        Assert::email($email);

        $signer = new Model\Signer([
            'email' => $email,
            'name' => $name,
            'recipient_id' => $this->docReference,
            'client_user_id' => $this->accountId, // Setting the client_user_id marks the signer as embedded
        ]);

        $signer->setTabs(new Model\Tabs(['sign_here_tabs' => $this->signatureZones]));

        $this->signers[] = $signer;

        return $this;
    }

    private function defineEnvelope(): void
    {
        $this->envelopeDefinition = new Model\EnvelopeDefinition([
            'email_subject' => self::EMAIL_SUBJECT,
            'documents' => [$this->document],
            'recipients' => new Model\Recipients(['signers' => $this->signers, 'carbon_copies' => $this->carbonCopies ?? null]),
            'status' => 'sent',
            'event_notification' => $this->getEventsNotifications(),
        ]);
    }

    /**
     * @throws \DocuSign\eSign\ApiException
     */
    private function sendEnvelope(): void
    {
        $this->setUpConfiguration();
        $result = $this->envelopesApi->createEnvelope($this->accountId, $this->envelopeDefinition);

        $this->envelopeId = $result['envelope_id'];
    }

    private function reset(): void
    {
        $this->docReference = time(); // Will stop working after the 19/01/2038 at 03:14:07. (high five If you guess why)
        $this->filePath = null;
        $this->signatureZones = [];
        $this->document = null;
        $this->signers = [];
        $this->carbonCopies = [];
        $this->envelopeDefinition = null;
        $this->apiClient = null;
        $this->envelopesApi = null;
        $this->envelopeId = null;
    }

    /**
     * @throws ApiException
     */
    private function createRecipient(): Model\ViewUrl
    {
        $recipientViewRequest = new Model\RecipientViewRequest([
            'authentication_method' => self::EMBEDDED_AUTHENTICATION_METHOD,
            'client_user_id' => $this->accountId,
            'recipient_id' => '1',
            'return_url' => $this->router->generate($this->callBackRouteName, ['envelopeId' => $this->envelopeId], Router::ABSOLUTE_URL),
            'user_name' => $this->signerName,
            'email' => $this->signerEmail,
        ]);

        return $this->envelopesApi->createRecipientView($this->accountId, $this->envelopeId, $recipientViewRequest);
    }

    /**
     * @throws ApiException
     */
    public function getEnvelopeDocuments(string $envelopeId): array
    {
        $documents = [];
        $this->setUpConfiguration();
        $docsList = $this->envelopesApi->listDocuments($this->accountId, $envelopeId);
        foreach ($docsList->getEnvelopeDocuments() as $document) {
            $documents[] = $this->envelopesApi->getDocument($this->accountId, $document->getDocumentId(), $envelopeId);
        }

        return $documents;
    }

    private function setUpConfiguration(): void
    {
        $this->config = new Configuration();
        $this->config->setHost($this->apiURI);
        $this->config->addDefaultHeader('Authorization', "Bearer {$this->accessToken}");
        $this->apiClient = new ApiClient($this->config);
        $this->envelopesApi = new EnvelopesApi($this->apiClient);
    }
}
