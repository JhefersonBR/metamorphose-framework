<?php

namespace Metamorphose\Tests\Unit\Context;

use Metamorphose\Kernel\Context\UnitContext;
use PHPUnit\Framework\TestCase;

class UnitContextTest extends TestCase
{
    public function testUnitIdCanBeSetAndRetrieved(): void
    {
        $context = new UnitContext();
        
        $this->assertNull($context->getUnitId());
        
        $context->setUnitId('unit123');
        
        $this->assertEquals('unit123', $context->getUnitId());
    }

    public function testUnitCodeCanBeSetAndRetrieved(): void
    {
        $context = new UnitContext();
        
        $this->assertNull($context->getUnitCode());
        
        $context->setUnitCode('UNIT01');
        
        $this->assertEquals('UNIT01', $context->getUnitCode());
    }

    public function testUnitDataCanBeSetAndRetrieved(): void
    {
        $context = new UnitContext();
        
        $data = ['name' => 'Unit 1', 'location' => 'Building A'];
        $context->setUnitData($data);
        
        $this->assertEquals($data, $context->getUnitData());
    }

    public function testHasUnitReturnsFalseWhenNoUnit(): void
    {
        $context = new UnitContext();
        
        $this->assertFalse($context->hasUnit());
    }

    public function testHasUnitReturnsTrueWhenUnitSet(): void
    {
        $context = new UnitContext();
        $context->setUnitId('unit123');
        
        $this->assertTrue($context->hasUnit());
    }

    public function testClearResetsContext(): void
    {
        $context = new UnitContext();
        
        $context->setUnitId('unit123');
        $context->setUnitCode('UNIT01');
        $context->setUnitData(['name' => 'Unit 1']);
        
        $context->clear();
        
        $this->assertNull($context->getUnitId());
        $this->assertNull($context->getUnitCode());
        $this->assertEmpty($context->getUnitData());
        $this->assertFalse($context->hasUnit());
    }
}

