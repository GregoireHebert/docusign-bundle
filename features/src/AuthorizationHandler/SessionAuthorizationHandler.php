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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class SessionAuthorizationHandler implements AuthorizationCodeHandlerInterface
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function read(array $context = []): ?string
    {
        return $this->getSession()->get('docusign', [])[$context['signature_name'] ?? 'default'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $code, array $context = []): void
    {
        $docusign = $this->getSession()->get('docusign', []);
        $docusign[$context['signature_name'] ?? 'default'] = $code;
        $this->getSession()->set('docusign', $docusign);
    }

    private function getSession(): Session
    {
        return $this->requestStack->getCurrentRequest()->getSession();
    }
}
