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

namespace DocusignBundle;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeCreator\EnvelopeCreatorInterface;
use League\Flysystem\FilesystemInterface;
use Webmozart\Assert\Assert;

final class EnvelopeBuilder implements EnvelopeBuilderInterface
{
    public const EMBEDDED_AUTHENTICATION_METHOD = 'NONE';
    public const MODE_REMOTE = 'remote';
    public const MODE_EMBEDDED = 'embedded';
    public const MODE_CLICKWRAP = 'clickwrap';
    public const AUTH_MODE_JWT = 'jwt';
    public const AUTH_MODE_CODE = 'code';

    /** @var int */
    private $accountId;
    /** @var string */
    private $signerName;
    /** @var string */
    private $signerEmail;
    /** @var string */
    private $apiUri;
    /** @var string|null */
    private $filePath;
    /** @var int */
    private $docReference;
    /** @var FilesystemInterface */
    private $fileSystem;
    /** @var Model\Document|null */
    private $document;
    /** @var Model\EnvelopeDefinition|null */
    private $envelopeDefinition;
    /** @var EnvelopesApi|null */
    private $envelopesApi;
    /** @var string|null */
    private $envelopeId;
    /** @var string */
    private $callback;
    /** @var array */
    private $callbackParameters = [];
    /** @var Model\Signer[]|array */
    private $signers = [];
    /** @var Model\CarbonCopy[]|array */
    private $carbonCopies = [];
    /** @var Model\SignHere[]|array */
    private $signatureZones = [];
    /** @var ApiClient|null */
    private $apiClient;
    /** @var array */
    private $webhookParameters = [];
    /** @var string */
    private $mode;
    /** @var string */
    private $authMode;
    /** @var int */
    private $signatureNo = 1;
    /** @var EnvelopeCreatorInterface */
    private $envelopeCreator;
    /** @var string */
    private $name;

    public function __construct(
        FilesystemInterface $storage,
        EnvelopeCreatorInterface $envelopeCreator,
        int $accountId,
        string $defaultSignerName,
        string $defaultSignerEmail,
        bool $demo,
        string $apiUri,
        string $callback,
        string $mode,
        string $authMode,
        string $name
    ) {
        $this->envelopeCreator = $envelopeCreator;
        $this->fileSystem = $storage;
        $this->accountId = $accountId;

        $this->signerName = $defaultSignerName;
        $this->signerEmail = $defaultSignerEmail;

        $this->apiUri = $demo ? 'https://demo.docusign.net/restapi' : $apiUri;
        $this->callback = $callback;

        $this->mode = $mode;
        $this->authMode = $authMode;
        $this->name = $name;

        $this->docReference = time();
    }

    public function setFile(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function createEnvelope(): string
    {
        return $this->envelopeCreator->createEnvelope($this);
    }

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

    public function addCallbackParameter($name, $value): self
    {
        $this->callbackParameters[$name] = $value;

        return $this;
    }

    public function addWebhookParameter($name, $value): self
    {
        $this->webhookParameters[$name] = $value;

        return $this;
    }

    public function addSigner(string $name, string $email): self
    {
        if (empty($this->signatureZones)) {
            throw new \LogicException('You must create the signature zones before');
        }

        Assert::email($email);

        $data = [
            'email' => $email,
            'name' => $name,
            'recipient_id' => $this->docReference,
        ];

        if (self::MODE_EMBEDDED === $this->getMode()) {
            // Setting the client_user_id marks the signer as embedded
            $data['client_user_id'] = $this->accountId;
        }

        $signer = new Model\Signer($data);

        $signer->setTabs(new Model\Tabs(['sign_here_tabs' => $this->signatureZones]));

        $this->signers[] = $signer;

        return $this;
    }

    public function setEnvelopeDefinition(?Model\EnvelopeDefinition $envelopeDefinition): void
    {
        $this->envelopeDefinition = $envelopeDefinition;
    }

    public function setEnvelopesApi(?EnvelopesApi $envelopesApi): void
    {
        $this->envelopesApi = $envelopesApi;
    }

    public function setEnvelopeId(?string $envelopeId): void
    {
        $this->envelopeId = $envelopeId;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getAuthMode(): string
    {
        return $this->authMode;
    }

    public function reset(): void
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
        $this->webhookParameters = [];
    }

    public function getEnvelopesApi(): ?EnvelopesApi
    {
        return $this->envelopesApi;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getEnvelopeDefinition(): ?Model\EnvelopeDefinition
    {
        return $this->envelopeDefinition;
    }

    public function getApiUri(): string
    {
        return $this->apiUri;
    }

    public function getDocument(): ?Model\Document
    {
        return $this->document;
    }

    /**
     * @return array|Model\Signer[]
     */
    public function getSigners(): array
    {
        return $this->signers;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getFileSystem(): FilesystemInterface
    {
        return $this->fileSystem;
    }

    /**
     * @return false|string
     */
    public function getFileContent()
    {
        return $this->getFileSystem()->read($this->getFilePath());
    }

    public function getViewUrl(Model\RecipientViewRequest $recipientViewRequest): string
    {
        return $this->envelopesApi->createRecipientView((string) $this->getAccountId(), $this->getEnvelopeId(), $recipientViewRequest)->getUrl();
    }

    public function getSignerName(): string
    {
        return $this->signerName;
    }

    public function getSignerEmail(): string
    {
        return $this->signerEmail;
    }

    public function setSignerName(string $signerName): void
    {
        $this->signerName = $signerName;
    }

    public function setSignerEmail(string $signerEmail): void
    {
        $this->signerEmail = $signerEmail;
    }

    public function getDocReference(): int
    {
        return $this->docReference;
    }

    public function getCallback(): string
    {
        return $this->callback;
    }

    public function getCallbackParameters(): array
    {
        return $this->callbackParameters;
    }

    public function getWebhookParameters(): array
    {
        return $this->webhookParameters;
    }

    public function getEnvelopeId(): ?string
    {
        return $this->envelopeId;
    }

    public function setDocument(?Model\Document $document): void
    {
        $this->document = $document;
    }

    /**
     * @return array|Model\CarbonCopy[]
     */
    public function getCarbonCopies()
    {
        return $this->carbonCopies;
    }

    public function setDefaultSigner(): void
    {
        $this->addSigner($this->getSignerName(), $this->getSignerEmail());
    }
}
