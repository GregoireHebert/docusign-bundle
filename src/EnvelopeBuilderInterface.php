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
use DocuSign\eSign\Model;
use League\Flysystem\FilesystemInterface;

interface EnvelopeBuilderInterface
{
    public function setFile(string $filePath);

    public function getName(): string;

    public function createEnvelope(): string;

    public function addCarbonCopy(string $name, string $email);

    public function addSignatureZone(int $pageNumber, int $xPosition, int $yPosition);

    public function addCallbackParameter($name, $value);

    public function addWebhookParameter($name, $value);

    public function addSigner(string $name, string $email);

    public function setEnvelopeDefinition(?Model\EnvelopeDefinition $envelopeDefinition): void;

    public function setEnvelopesApi(?EnvelopesApi $envelopesApi): void;

    public function setEnvelopeId(?string $envelopeId): void;

    public function getMode(): string;

    public function getAuthMode(): string;

    public function getEnvelopesApi(): ?EnvelopesApi;

    public function reset(): void;

    public function getAccountId(): int;

    public function getEnvelopeDefinition(): ?Model\EnvelopeDefinition;

    public function getApiUri(): string;

    public function getDocument(): ?Model\Document;

    public function getSigners(): array;

    public function getFilePath(): ?string;

    public function getFileSystem(): FilesystemInterface;

    /**
     * @return false|string
     */
    public function getFileContent();

    public function getViewUrl(Model\RecipientViewRequest $recipientViewRequest): string;

    public function getSignerName(): string;

    public function getSignerEmail(): string;

    public function getDocReference(): int;

    public function getCallback(): string;

    public function getCallbackParameters(): array;

    public function getWebhookParameters(): array;

    public function getEnvelopeId(): ?string;

    public function setDocument(?Model\Document $document): void;

    public function getCarbonCopies();

    public function setDefaultSigner(): void;
}
