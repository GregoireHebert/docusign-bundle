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

namespace DocusignBundle\Utils;

use DocusignBundle\Exception\AmbiguousDocumentSelectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignatureExtractor
{
    private $defaultSignatures = [];
    private $signaturesOverridable = false;
    private $requestStack;

    public function __construct(RequestStack $requestStack, bool $isOverridable, array $signatures)
    {
        $this->requestStack = $requestStack;
        $this->signaturesOverridable = $isOverridable;
        $this->defaultSignatures = $signatures;
    }

    public function getSignatures(): ?array
    {
        return $this->extractSignatureFromRequest() ?? $this->getDefaultSignature();
    }

    private function getDefaultSignature(): ?array
    {
        $signaturesDefined = \count($this->defaultSignatures);

        if (0 === $signaturesDefined) {
            return null;
        }

        if ($request = $this->requestStack->getCurrentRequest()) {
            $documentType = $request->query->get('document_type');
        }

        if (!empty($documentType)) {
            return $this->defaultSignatures[$documentType] ?? null;
        }

        if (1 === $signaturesDefined) {
            return $this->defaultSignatures[array_keys($this->defaultSignatures)[0]] ?? null;
        }

        if (2 >= $signaturesDefined) {
            throw new AmbiguousDocumentSelectionException(sprintf('The document type is ambiguous. It should be one of %s', implode(' or ', array_keys($this->defaultSignatures))));
        }

        return null;
    }

    private function extractSignatureFromRequest(): ?array
    {
        if (false === $this->signaturesOverridable || !($request = $this->requestStack->getCurrentRequest())) {
            return null;
        }

        /** @var array|null $signatures */
        $signatures = $request->query->get('signatures');

        if (null === $signatures) {
            return null;
        }

        if (!\is_array($signatures)) {
            throw new \InvalidArgumentException('The parameter `signatures` must be an array of signatures, with the `page` (optional, default is 1), the `x_position` and the `y_position` values.');
        }

        $resolver = new OptionsResolver();
        $this->configureSignatureOptions($resolver);

        foreach ($signatures as &$signature) {
            $signature = $resolver->resolve($signature);
        }

        return $signatures;
    }

    private function configureSignatureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['page', 'x_position', 'y_position']);

        $resolver->setDefault('page', 1);

        $resolver->setAllowedTypes('page', 'int');
        $resolver->setAllowedTypes('x_position', 'int');
        $resolver->setAllowedTypes('y_position', 'int');
    }
}
