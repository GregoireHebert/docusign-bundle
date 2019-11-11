<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilder;
use League\Flysystem\FileNotFoundException;

class CreateDocument
{
    public function handle(EnvelopeBuilder $envelopeBuilder)
    {
        if (false === $contentBytes = $envelopeBuilder->fileSystem->read($envelopeBuilder->filePath)) {
            throw new FileNotFoundException($envelopeBuilder->filePath ?? 'null');
        }

        $base64FileContent = base64_encode($contentBytes);
        ['extension' => $extension, 'filename' => $filename] = pathinfo($envelopeBuilder->filePath);

        $envelopeBuilder->document = new Model\Document([
            'document_base64' => $base64FileContent,
            'name' => $filename,
            'file_extension' => $extension,
            'document_id' => $envelopeBuilder->docReference,
        ]);

        $envelopeBuilder->addSigner($envelopeBuilder->signerName, $envelopeBuilder->signerEmail);

        return $envelopeBuilder;
    }
}
