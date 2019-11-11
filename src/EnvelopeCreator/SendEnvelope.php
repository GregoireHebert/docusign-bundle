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

    public function handle(EnvelopeBuilder $envelopeBuilder): void
    {
        $envelopeBuilder->envelopesApi = $this->setUpConfiguration($envelopeBuilder);
        $result = $envelopeBuilder->envelopesApi->createEnvelope($envelopeBuilder->accountId, $envelopeBuilder->envelopeDefinition);

        $envelopeBuilder->envelopeId = $result['envelope_id'];
    }

    private function setUpConfiguration(EnvelopeBuilder $envelopeBuilder): EnvelopesApi
    {
        $config = new Configuration();
        $config->setHost($envelopeBuilder->apiUri);
        $config->addDefaultHeader('Authorization', 'Bearer '.($this->grant)());

        return new EnvelopesApi(new ApiClient($config));
    }
}
