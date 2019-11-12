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

use DocuSign\eSign\ApiException;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Exception\UnableToSignException;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Webmozart\Assert\Assert;

final class EnvelopeCreator implements EnvelopeCreatorInterface
{
    private $router;
    private $logger;
    private $stopwatch;
    /** @var EnvelopeBuilderCallableInterface[]|iterable */
    private $actions;
    private $signatureName;

    public function __construct(
        RouterInterface $router,
        LoggerInterface $logger,
        Stopwatch $stopwatch,
        string $signatureName,
        iterable $actions
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;
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
                $key = sprintf('[Docusign] execute action %s', \get_class($action));
                $this->stopwatch->start($key);

                if (!empty($result = $action($envelopeBuilder, ['signature_name' => $this->signatureName]))) {
                    $this->stopwatch->stop($key);
                    break;
                }

                $this->stopwatch->stop($key);
            }

            return $result;
        } catch (ApiException $exception) {
            $this->logger->critical('Unable to send a document to DocuSign.', [
                'document' => $envelopeBuilder->getDocument(),
                'signers' => $envelopeBuilder->getSigners(),
                'envelope' => $envelopeBuilder->getEnvelopeDefinition(),
                'request' => $exception->getResponseBody(),
            ]);
            if (!empty($key)) {
                $this->stopwatch->stop($key);
            }

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
