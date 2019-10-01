<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\DocusignBundle;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Utils\SignatureExtractor;
use Nyholm\BundleTest\BaseBundleTestCase;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class     BundleInitializationTest extends BaseBundleTestCase
{
    protected function getBundleClass(): string
    {
        return DocusignBundle::class;
    }

    public function testInitBundle(): void
    {
        $kernel = $this->createKernel();
        $kernel->addConfigFile(__DIR__.'/config/docusign.yml');
        $this->bootKernel();

        $container = $this->getContainer();
        $this->assertTrue($container->has(Callback::class));
        $this->assertTrue($container->has('docusign_callback'));
        $this->assertTrue($container->has(Sign::class));
        $this->assertTrue($container->has('docusign_sign'));
        $this->assertTrue($container->has(Webhook::class));
        $this->assertTrue($container->has('docusign_webhook'));
        $this->assertFalse($container->has(SignatureExtractor::class));
        $this->assertTrue($container->has('docusign_signature_extractor'));
        $this->assertFalse($container->has(EnvelopeBuilder::class));
        $this->assertTrue($container->has('docusign_envelope_builder'));
    }
}
