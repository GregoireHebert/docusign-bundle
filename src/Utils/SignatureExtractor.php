<?php

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

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function setSignaturesOverridable(bool $isOverridable): void
    {
        $this->signaturesOverridable = $isOverridable;
    }

    public function setDefaultSignatures(array $signatures): void
    {
        $this->defaultSignatures = $signatures;
    }

    public function getSignatures(): ?array
    {
        return $this->extractSignatureFromRequest() ?? $this->getDefaultSignature();
    }

    private function getDefaultSignature(): ?array
    {
        $documentType = $this->requestStack->getCurrentRequest()->query->get('document_type');
        $signaturesDefined = \count($this->defaultSignatures);

        if (0 === $signaturesDefined) {
            return null;
        }

        if (null !== $documentType) {
            return $this->defaultSignatures[$documentType]['signatures'] ?? null;
        }

        if (null === $documentType && 1 === $signaturesDefined) {
            return array_shift($this->defaultSignatures)['signatures'] ?? null;
        }

        if (null === $documentType && 1 < $signaturesDefined) {
            throw new AmbiguousDocumentSelectionException(sprintf('The document type is ambiguous. It should be one of %s', implode(' or ', array_keys($this->defaultSignatures))));
        }

        return null;
    }

    private function extractSignatureFromRequest(): ?array
    {
        if (false === $this->signaturesOverridable) {
            return null;
        }

        $signatures = $this->requestStack->getCurrentRequest()->query->get('signatures');

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
