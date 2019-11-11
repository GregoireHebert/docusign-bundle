<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\ApiException;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Exception\UnableToSignException;
use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Webmozart\Assert\Assert;

class EnvelopeCreator
{
    private $router;
    private $logger;
    private $stopwatch;
    private $createDocument;
    private $createRecipient;
    private $defineEnvelope;
    private $sendEnvelope;

    public function __construct(
        RouterInterface $router,
        LoggerInterface $logger,
        Stopwatch $stopwatch,
        CreateDocument $createDocument,
        CreateRecipient $createRecipient,
        DefineEnvelope $defineEnvelope,
        SendEnvelope $sendEnvelope
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;
        $this->createDocument = $createDocument;
        $this->createRecipient = $createRecipient;
        $this->defineEnvelope = $defineEnvelope;
        $this->sendEnvelope = $sendEnvelope;
    }

    /**
     * @throws FileNotFoundException
     *
     * @return string path to redirect
     *
     * @throw InvalidArgumentException
     */
    public function createEnvelope(EnvelopeBuilder $envelopeBuilder): string
    {
        try {
            $this->validate($envelopeBuilder);

            $key = '[Docusign] Create document';
            $this->stopwatch->start($key);
            $this->createDocument->handle($envelopeBuilder);
            $this->stopwatch->stop($key);

            $this->defineEnvelope->handle($envelopeBuilder);

            $key = '[Docusign] Send envelope';
            $this->stopwatch->start($key);
            $this->sendEnvelope->handle($envelopeBuilder);
            $this->stopwatch->stop($key);

            if (EnvelopeBuilder::MODE_REMOTE === $envelopeBuilder->mode) {
                return $this->getCallbackRoute($envelopeBuilder);
            }

            $key = '[Docusign] Create recipient';
            $this->stopwatch->start($key);
            $viewUrl = $this->createRecipient->handle($envelopeBuilder);
            $this->stopwatch->stop($key);

            return $viewUrl->getUrl();
        } catch (ApiException $exception) {
            $this->logger->critical('Unable to send a document to DocuSign.', [
                'document' => $envelopeBuilder->document,
                'signers' => $envelopeBuilder->signers,
                'envelope' => $envelopeBuilder->envelopeDefinition,
                'request' => $exception->getResponseBody(),
            ]);
            if (!empty($key)) {
                $this->stopwatch->stop($key);
            }

            throw new UnableToSignException($exception->getMessage());
        } finally {
            $this->reset($envelopeBuilder);
        }
    }

    private function validate(EnvelopeBuilder $envelopeBuilder): void
    {
        Assert::notNull($envelopeBuilder->filePath);
        Assert::notEmpty($envelopeBuilder->docReference);
    }

    /**
     * Returns the callback.
     */
    private function getCallbackRoute(EnvelopeBuilder $envelopeBuilder): string
    {
        try {
            Assert::regex($envelopeBuilder->callbackRouteName, '#^https?://.+(:[0-9]+)?$#');

            return $envelopeBuilder->callbackRouteName;
        } catch (InvalidArgumentException $exception) {
            return $this->router->generate($envelopeBuilder->callbackRouteName, array_unique(['envelopeId' => $envelopeBuilder->envelopeId] + $envelopeBuilder->callbackParameters), Router::ABSOLUTE_URL);
        }
    }

    public function reset(EnvelopeBuilder $envelopeBuilder): void
    {
        $envelopeBuilder->docReference = time(); // Will stop working after the 19/01/2038 at 03:14:07. (high five If you guess why)
        $envelopeBuilder->filePath = null;
        $envelopeBuilder->signatureZones = [];
        $envelopeBuilder->document = null;
        $envelopeBuilder->signers = [];
        $envelopeBuilder->carbonCopies = [];
        $envelopeBuilder->envelopeDefinition = null;
        $envelopeBuilder->apiClient = null;
        $envelopeBuilder->envelopesApi = null;
        $envelopeBuilder->envelopeId = null;
    }
}
