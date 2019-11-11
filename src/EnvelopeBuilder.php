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
use League\Flysystem\FilesystemInterface;
use Webmozart\Assert\Assert;

class EnvelopeBuilder
{
    public const EMBEDDED_AUTHENTICATION_METHOD = 'NONE';
    public const EMAIL_SUBJECT = 'Please sign this document';
    public const MODE_REMOTE = 'remote';
    public const MODE_EMBEDDED = 'embedded';

    /** @var string */
    public $accountId;
    /** @var string */
    public $signerName;
    /** @var string */
    public $signerEmail;
    /** @var string */
    public $apiUri;
    /** @var string|null */
    public $filePath;
    /** @var int */
    public $docReference;
    /** @var FilesystemInterface */
    public $fileSystem;
    /** @var Model\Document|null */
    public $document;
    /** @var Model\EnvelopeDefinition|null */
    public $envelopeDefinition;
    /** @var EnvelopesApi|null */
    public $envelopesApi;
    /** @var string|null */
    public $envelopeId;
    /** @var string */
    public $callback;
    /** @var array */
    public $callbackParameters = [];
    /** @var Model\Signer[]|array */
    public $signers = [];
    /** @var Model\CarbonCopy[]|array */
    public $carbonCopies = [];
    /** @var Model\SignHere[]|array */
    public $signatureZones = [];
    /** @var ApiClient|null */
    public $apiClient;
    /** @var string */
    public $webhookRouteName;
    /** @var array */
    public $webhookParameters = [];
    /** @var string */
    public $mode;
    /** @var int */
    private $signatureNo = 1;
    /** @var EnvelopeCreator */
    private $envelopeCreator;

    public function __construct(
        FilesystemInterface $storage,
        EnvelopeCreator $envelopeCreator,
        string $accountId,
        string $defaultSignerName,
        string $defaultSignerEmail,
        string $apiUri,
        string $callback,
        string $webhookRouteName,
        string $mode
    ) {
        $this->envelopeCreator = $envelopeCreator;
        $this->fileSystem = $storage;
        $this->accountId = $accountId;

        $this->signerName = $defaultSignerName;
        $this->signerEmail = $defaultSignerEmail;

        $this->apiUri = $apiUri;
        $this->callback = $callback;
        $this->webhookRouteName = $webhookRouteName;

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
}
