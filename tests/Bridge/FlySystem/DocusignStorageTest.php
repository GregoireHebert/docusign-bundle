<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Bridge\FlySystem\DocusignStorage;
use League\Flysystem\AdapterInterface;
use PHPUnit\Framework\TestCase;

class DocusignStorageTest extends TestCase
{
    public function testRead(): void
    {
        $adapterProphecy = $this->prophesize(AdapterInterface::class);
        $adapterProphecy->read('myPath')->willReturn(['content' => 'DummyContent']);
        $docusignStorage = new DocusignStorage($adapterProphecy->reveal());
        $this->assertEquals('DummyContent', $docusignStorage->read('myPath'));
    }
}
