<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem\Adapter;

use AppBundle\Wrapper\GedWrapper;
use DocusignBundle\Bridge\FlySystem\Adapter\SharePointAdapter;
use PHPUnit\Framework\TestCase;

class SharePointAdapterTest extends TestCase
{
    public function testRead(): void
    {
        $gedWrapperProphecy = $this->prophesize(GedWrapper::class);
        $gedWrapperProphecy->getFile('myPath')->willReturn('DummyContent');
        $sharepointAdapter = new SharePointAdapter($gedWrapperProphecy->reveal());
        $this->assertArrayHasKey('content', $read = $sharepointAdapter->read('myPath'));
        $this->assertEquals('DummyContent', $read['content']);
    }
}
