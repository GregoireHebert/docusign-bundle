<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Configuration;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Grant\GrantInterface;

class SendEnvelope
{
    public $grant;

    public function __construct(GrantInterface $grant)
    {
        $this->grant = $grant;
    }

    /**
     * @throws \DocuSign\eSign\ApiException
     */
    public function handle(EnvelopeBuilder $envelopeBuilder): void
    {
        $envelopeBuilder->setEnvelopesApi($this->setUpConfiguration($envelopeBuilder));
        $envelopeBuilder->setEnvelopeId($envelopeBuilder->envelopesApi->createEnvelope($envelopeBuilder->accountId, $envelopeBuilder->envelopeDefinition)->getEnvelopeId());
    }

    private function setUpConfiguration(EnvelopeBuilder $envelopeBuilder): EnvelopesApi
    {
        $config = new Configuration();
        $config->setHost($envelopeBuilder->apiUri);
        $config->addDefaultHeader('Authorization', 'Bearer '.($this->grant)());

        return new EnvelopesApi(new ApiClient($config));
    }
}
