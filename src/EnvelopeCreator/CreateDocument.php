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
use League\Flysystem\FileNotFoundException;

final class CreateDocument implements EnvelopeBuilderCallableInterface
{
    public function __invoke(EnvelopeBuilderInterface $envelopeBuilder, array $context = []): void
    {
        if (false === $contentBytes = $envelopeBuilder->getFileSystem()->read($envelopeBuilder->getFilePath())) {
            throw new FileNotFoundException($envelopeBuilder->getFilePath() ?? 'null');
        }

        $base64FileContent = base64_encode($contentBytes);
        ['extension' => $extension, 'filename' => $filename] = pathinfo($envelopeBuilder->getFilePath());

        $envelopeBuilder->setDocument(new Model\Document([
            'document_base64' => $base64FileContent,
            'name' => $filename,
            'file_extension' => $extension,
            'document_id' => $envelopeBuilder->getDocReference(),
        ]));

        $envelopeBuilder->addSigner($envelopeBuilder->getSignerName(), $envelopeBuilder->getSignerEmail());
    }
}