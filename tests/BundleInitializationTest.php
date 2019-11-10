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
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Utils\SignatureExtractor;
use Nyholm\BundleTest\BaseBundleTestCase;

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

        $this->assertFalse($container->has('docusign.grant.default'));
        $this->assertFalse($container->has(JwtGrant::class));
        $this->assertFalse($container->has(GrantInterface::class));
        $this->assertFalse($container->has('docusign.signature_extractor.default'));
        $this->assertFalse($container->has(SignatureExtractor::class));
        $this->assertFalse($container->has('docusign.envelope_builder.default'));
        $this->assertFalse($container->has(EnvelopeBuilder::class));
    }
}
