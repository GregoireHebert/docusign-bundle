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
use DocusignBundle\Utils\CallbackRouteGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class Consent
{
    public const DEMO_CONSENT_URI = 'https://account-d.docusign.com/oauth/auth';
    public const CONSENT_URI = 'https://account.docusign.com/oauth/auth';
    public const RESPONSE_TYPE = [
        'authorization_code' => 'code',
        'implicit' => 'token',
    ];

    private $envelopeBuilder;
    private $router;
    private $consentUri;
    private $integrationKey;
    private $responseType;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router, string $consentUri, string $integrationKey, string $responseType)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->router = $router;
        $this->consentUri = $consentUri;
        $this->integrationKey = $integrationKey;
        $this->responseType = $responseType;
    }

    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse(sprintf(
            '%s?response_type=%s&scope=signature%%20impersonation&client_id=%s&redirect_uri=%s',
            $this->consentUri,
            $this->responseType,
            $this->integrationKey,
            CallbackRouteGenerator::getCallbackRoute($this->router, $this->envelopeBuilder)
        ), 301);
    }
}
