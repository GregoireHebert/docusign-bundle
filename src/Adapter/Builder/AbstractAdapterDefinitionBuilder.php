<?php

declare(strict_types=1);

namespace DocusignBundle\Adapter\Builder;

use DocusignBundle\Exception\MissingPackageException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * flysystem compatibility.
 */
abstract class AbstractAdapterDefinitionBuilder implements AdapterDefinitionBuilderInterface
{
    final public function createDefinition(array $options): Definition
    {
        $this->ensureRequiredPackagesAvailable();

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $definition = new Definition();
        $definition->setPublic(false);
        $this->configureDefinition($definition, $resolver->resolve($options));

        return $definition;
    }

    abstract protected function getRequiredPackages(): array;

    abstract protected function configureOptions(OptionsResolver $resolver);

    abstract protected function configureDefinition(Definition $definition, array $options);

    private function ensureRequiredPackagesAvailable(): void
    {
        $missingPackages = [];
        foreach ($this->getRequiredPackages() as $requiredClass => $packageName) {
            if (!class_exists($requiredClass)) {
                $missingPackages[] = $packageName;
            }
        }

        if (!$missingPackages) {
            return;
        }

        throw new MissingPackageException(sprintf(
            "Missing package%s, to use the \"%s\" adapter, run:\n\ncomposer require %s",
            \count($missingPackages) > 1 ? 's' : '',
            $this->getName(),
            implode(' ', $missingPackages)
        ));
    }
}
