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

use DocuSign\eSign\Client\ApiException;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Exception\FileNotFoundException;
use DocusignBundle\Exception\UnableToSignException;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class EnvelopeCreator implements EnvelopeCreatorInterface
{
    private $logger;
    /** @var EnvelopeBuilderCallableInterface[]|iterable */
    private $actions;
    private $signatureName;

    public function __construct(
        LoggerInterface $logger,
        string $signatureName,
        iterable $actions
    ) {
        $this->logger = $logger;
        $this->actions = $actions;
        $this->signatureName = $signatureName;
    }

    /**
     * @throws FileNotFoundException
     *
     * @return string path to redirect
     *
     * @throw InvalidArgumentException
     */
    public function createEnvelope(EnvelopeBuilderInterface $envelopeBuilder): string
    {
        try {
            $this->validate($envelopeBuilder);
            $result = null;

            foreach ($this->actions as $action) {
                if (!empty($result = $action(['signature_name' => $this->signatureName]))) {
                    break;
                }
            }

            return $result;
        } catch (ApiException $exception) {
            $this->logger->critical('Unable to send a document to DocuSign.', [
                'document' => $envelopeBuilder->getDocument(),
                'signers' => $envelopeBuilder->getSigners(),
                'envelope' => $envelopeBuilder->getEnvelopeDefinition(),
                'request' => $exception->getResponseBody(),
            ]);

            throw new UnableToSignException($exception->getMessage());
        } finally {
            $envelopeBuilder->reset();
        }
    }

    private function validate(EnvelopeBuilderInterface $envelopeBuilder): void
    {
        Assert::notNull($envelopeBuilder->getFilePath());
        Assert::notEmpty($envelopeBuilder->getDocReference());
    }
}
