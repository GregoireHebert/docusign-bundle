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

namespace DocusignBundle\Tests;

use DocusignBundle\DocusignBundle;
use Nyholm\BundleTest\BaseBundleTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class BundleInitializationTest extends BaseBundleTestCase
{
    protected function getBundleClass(): string
    {
        return DocusignBundle::class;
    }

    public function testTheBundleIsBootable(): void
    {
        $kernel = $this->createKernel();
        $kernel->addConfigFile(__DIR__.'/config/docusign.yml');
        $this->bootKernel();

        $container = $this->getContainer();

        $this->assertFalse($container->has('docusign.grant.embedded'));
        $this->assertFalse($container->has('docusign.token_encoder.embedded'));
        $this->assertFalse($container->has('docusign.signature_extractor.embedded'));
        $this->assertFalse($container->has('docusign.envelope_builder.embedded'));
    }

    /**
     * @dataProvider getInvalidConfigurationFiles
     */
    public function testTheBundleIsNotBootable(string $configFile): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $kernel = $this->createKernel();
        $kernel->addConfigFile($configFile);
        $this->bootKernel();
    }

    public function getInvalidConfigurationFiles(): array
    {
        return [
            [__DIR__.'/config/invalidAccountId.yml'],
            [__DIR__.'/config/invalidAuthClickwrap.yml'],
            [__DIR__.'/config/invalidAuthJwt.yml'],
            [__DIR__.'/config/invalidSignPath.yml'],
            [__DIR__.'/config/invalidStorage.yml'],
        ];
    }
}
