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
use DocusignBundle\EnvelopeCreator\EnvelopeCreator;
use DocusignBundle\EnvelopeCreator\EnvelopeCreatorInterface;
use League\Flysystem\FilesystemInterface;
use Webmozart\Assert\Assert;

final class EnvelopeBuilder implements EnvelopeBuilderInterface
{
    public const EMBEDDED_AUTHENTICATION_METHOD = 'NONE';
    public const MODE_REMOTE = 'remote';
    public const MODE_EMBEDDED = 'embedded';

    /** @var string */
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
    /** @var int */
    private $signatureNo = 1;
    /** @var EnvelopeCreator */
    private $envelopeCreator;

    public function __construct(
        FilesystemInterface $storage,
        EnvelopeCreatorInterface $envelopeCreator,
        string $accountId,
        string $defaultSignerName,
        string $defaultSignerEmail,
        string $apiUri,
        string $callback,
        string $mode
    ) {
        $this->envelopeCreator = $envelopeCreator;
        $this->fileSystem = $storage;
        $this->accountId = $accountId;

        $this->signerName = $defaultSignerName;
        $this->signerEmail = $defaultSignerEmail;

        $this->apiUri = $apiUri;
        $this->callback = $callback;

        $this->mode = $mode;

        $this->docReference = time();
    }

    public function setFile(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function createEnvelope(): string
    {
        return $this->envelopeCreator->createEnvelope($this);
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

    public function addCallbackParameter($parameter): self
    {
        $this->callbackParameters[] = $parameter;

        return $this;
    }

    public function addWebhookParameter($parameter): self
    {
        $this->webhookParameters[] = $parameter;

        return $this;
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

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
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
    }

    /**
     * @return EnvelopesApi|null
     */
    public function getEnvelopesApi(): ?EnvelopesApi
    {
        return $this->envelopesApi;
    }

    /**
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @return Model\EnvelopeDefinition|null
     */
    public function getEnvelopeDefinition(): ?Model\EnvelopeDefinition
    {
        return $this->envelopeDefinition;
    }

    /**
     * @return string
     */
    public function getApiUri(): string
    {
        return $this->apiUri;
    }

    /**
     * @return Model\Document|null
     */
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

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem(): FilesystemInterface
    {
        return $this->fileSystem;
    }

    /**
     * @return string
     */
    public function getSignerName(): string
    {
        return $this->signerName;
    }

    /**
     * @return string
     */
    public function getSignerEmail(): string
    {
        return $this->signerEmail;
    }

    /**
     * @return int
     */
    public function getDocReference(): int
    {
        return $this->docReference;
    }

    /**
     * @return string
     */
    public function getCallback(): string
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getCallbackParameters(): array
    {
        return $this->callbackParameters;
    }

    /**
     * @return array
     */
    public function getWebhookParameters(): array
    {
        return $this->webhookParameters;
    }

    /**
     * @return string|null
     */
    public function getEnvelopeId(): ?string
    {
        return $this->envelopeId;
    }
}
