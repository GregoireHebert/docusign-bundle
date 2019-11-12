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

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Configuration;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Utils\CallbackRouteGenerator;
use Symfony\Component\Routing\RouterInterface;

final class SendEnvelope implements EnvelopeBuilderCallableInterface
{
    public $grant;
    private $router;
    private $signatureName;

    public function __construct(GrantInterface $grant, RouterInterface $router, string $signatureName)
    {
        $this->grant = $grant;
        $this->router = $router;
        $this->signatureName = $signatureName;
    }

    /**
     * @throws \DocuSign\eSign\ApiException
     */
    public function __invoke(EnvelopeBuilderInterface $envelopeBuilder, array $context = [])
    {
        if ($context['signature_name'] !== $this->signatureName) {
            return;
        }

        $envelopeBuilder->setEnvelopesApi($this->setUpConfiguration($envelopeBuilder));
        $envelopeBuilder->setEnvelopeId($envelopeBuilder->getEnvelopesApi()->createEnvelope($envelopeBuilder->getAccountId(), $envelopeBuilder->getEnvelopeDefinition())->getEnvelopeId());

        if (EnvelopeBuilder::MODE_REMOTE === $envelopeBuilder->getMode()) {
            return CallbackRouteGenerator::getCallbackRoute($this->router, $envelopeBuilder);
        }
    }

    private function setUpConfiguration(EnvelopeBuilderInterface $envelopeBuilder): EnvelopesApi
    {
        $config = new Configuration();
        $config->setHost($envelopeBuilder->getApiUri());
        $config->addDefaultHeader('Authorization', 'Bearer '.($this->grant)());

        return new EnvelopesApi(new ApiClient($config));
    }
}
