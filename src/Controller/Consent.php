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

namespace DocusignBundle\Controller;

use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Exception\ConfigurationException;
use DocusignBundle\Utils\CallbackRouteGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class Consent
{
    public const DEMO_CONSENT_URI = 'https://account-d.docusign.com/oauth/auth';
    public const CONSENT_URI = 'https://account.docusign.com/oauth/auth';

    private $envelopeBuilder;
    private $router;
    private $consentUri;
    private $integrationKey;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router, string $consentUri, string $integrationKey)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->router = $router;
        $this->consentUri = $consentUri;
        $this->integrationKey = $integrationKey;

        $this->validateConfiguration();
    }

    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse(sprintf(
            '%s?response_type=token&scope=signature%%20impersonation&client_id=%s&redirect_uri=%s',
            $this->consentUri,
            $this->integrationKey,
            CallbackRouteGenerator::getCallbackRoute($this->router, $this->envelopeBuilder)
        ), 301);
    }

    private function validateConfiguration(): void
    {
        try {
            Assert::uuid($this->integrationKey);
        } catch (\Exception $e) {
            throw new ConfigurationException(sprintf('Your integration key "%s" is invalid. To generate your integration key, follow this documentation: https://developers.docusign.com/esign-soap-api/reference/Introduction-Changes/Integration-Keys', $this->integrationKey));
        }
    }
}
