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
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @group bootable
 */
final class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(DocusignBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testTheBundleIsBootable(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__.'/config/docusign.yml');
        }]);
        $container = $kernel->getContainer();

        $this->assertFalse($container->has('docusign.grant.embedded'));
        $this->assertFalse($container->has('docusign.signature_extractor.embedded'));
        $this->assertFalse($container->has('docusign.envelope_builder.embedded'));
    }

    /**
     * @dataProvider getInvalidConfigurationFiles
     */
    public function testTheBundleIsNotBootable(string $configFile): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::bootKernel(['config' => static function (TestKernel $kernel) use ($configFile): void {
            $kernel->addTestConfig($configFile);
        }]);
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
