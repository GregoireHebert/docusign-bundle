<?php

declare(strict_types=1);

namespace DocusignBundle\Utils;

use DocusignBundle\Exception\AmbiguousDocumentSelectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignatureExtractor
{
    private $defaultSignatures = [];
    private $signaturesOverridable = false;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
        $documentType = $this->request->get('documentType');
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

        $signatures = $this->request->get('signatures');

        if (null === $signatures) {
            return null;
        }

        if (!\is_array($signatures)) {
            throw new \InvalidArgumentException('The parameter `signatures` must be an array of signatures, with the `page` (optional, default is 1), the `xPosition` and the `yPosition` values.');
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
        $resolver->setRequired(['page', 'xPosition', 'yPosition']);

        $resolver->setDefault('page', 1);

        $resolver->setAllowedTypes('page', 'int');
        $resolver->setAllowedTypes('xPosition', 'int');
        $resolver->setAllowedTypes('yPosition', 'int');
    }
}
