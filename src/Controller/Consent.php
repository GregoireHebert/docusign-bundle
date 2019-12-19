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
    public const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    public const GRANT_TYPE_IMPLICIT = 'implicit';

    private $envelopeBuilder;
    private $router;
    private $consentUri;
    private $integrationKey;
    private $grantType;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router, string $consentUri, string $integrationKey, string $grantType)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->router = $router;
        $this->consentUri = $consentUri;
        $this->integrationKey = $integrationKey;
        $this->grantType = $grantType;
    }

    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse(sprintf(
            '%s?response_type=%s&scope=signature%%20impersonation&client_id=%s&redirect_uri=%s',
            $this->consentUri,
            self::GRANT_TYPE_AUTHORIZATION_CODE === $this->grantType ? 'code' : 'token',
            $this->integrationKey,
            CallbackRouteGenerator::getCallbackRoute($this->router, $this->envelopeBuilder)
        ), 301);
    }
}
