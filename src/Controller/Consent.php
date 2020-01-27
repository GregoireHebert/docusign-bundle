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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Consent
{
    public const DEMO_CONSENT_URI = 'https://account-d.docusign.com/oauth/auth';
    public const CONSENT_URI = 'https://account.docusign.com/oauth/auth';
    public const RESPONSE_TYPE = [
        'authorization_code' => 'code',
        'implicit' => 'token',
    ];

    private $consentUri;
    private $integrationKey;
    private $responseType;

    public function __construct(string $consentUri, string $integrationKey, string $responseType)
    {
        $this->consentUri = $consentUri;
        $this->integrationKey = $integrationKey;
        $this->responseType = $responseType;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        return new RedirectResponse(sprintf(
            '%s?response_type=%s&scope=signature%%20impersonation&client_id=%s&redirect_uri=%s',
            $this->consentUri,
            $this->responseType,
            $this->integrationKey,
            $request->getSchemeAndHttpHost()
        ), RedirectResponse::HTTP_TEMPORARY_REDIRECT);
    }
}
