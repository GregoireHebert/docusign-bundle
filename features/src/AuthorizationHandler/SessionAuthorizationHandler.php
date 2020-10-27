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

namespace DocusignBundle\E2e\AuthorizationHandler;

use DocusignBundle\AuthorizationCodeHandler\AuthorizationCodeHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class SessionAuthorizationHandler implements AuthorizationCodeHandlerInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function read(array $context = []): ?string
    {
        return $this->session->get('docusign', [])[$context['signature_name'] ?? 'default'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $code, array $context = []): void
    {
        $docusign = $this->session->get('docusign', []);
        $docusign[$context['signature_name'] ?? 'default'] = $code;
        $this->session->set('docusign', $docusign);
    }
}
