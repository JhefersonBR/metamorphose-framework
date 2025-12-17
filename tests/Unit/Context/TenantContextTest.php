<?php

namespace Metamorphose\Tests\Unit\Context;

use Metamorphose\Kernel\Context\TenantContext;
use PHPUnit\Framework\TestCase;

class TenantContextTest extends TestCase
{
    public function testTenantIdCanBeSetAndRetrieved(): void
    {
        $context = new TenantContext();
        
        $this->assertNull($context->getTenantId());
        
        $context->setTenantId('tenant123');
        
        $this->assertEquals('tenant123', $context->getTenantId());
    }

    public function testTenantCodeCanBeSetAndRetrieved(): void
    {
        $context = new TenantContext();
        
        $this->assertNull($context->getTenantCode());
        
        $context->setTenantCode('ACME');
        
        $this->assertEquals('ACME', $context->getTenantCode());
    }

    public function testTenantDataCanBeSetAndRetrieved(): void
    {
        $context = new TenantContext();
        
        $data = ['name' => 'Acme Corp', 'plan' => 'premium'];
        $context->setTenantData($data);
        
        $this->assertEquals($data, $context->getTenantData());
    }

    public function testHasTenantReturnsFalseWhenNoTenant(): void
    {
        $context = new TenantContext();
        
        $this->assertFalse($context->hasTenant());
    }

    public function testHasTenantReturnsTrueWhenTenantSet(): void
    {
        $context = new TenantContext();
        $context->setTenantId('tenant123');
        
        $this->assertTrue($context->hasTenant());
    }

    public function testClearResetsContext(): void
    {
        $context = new TenantContext();
        
        $context->setTenantId('tenant123');
        $context->setTenantCode('ACME');
        $context->setTenantData(['name' => 'Acme Corp']);
        
        $context->clear();
        
        $this->assertNull($context->getTenantId());
        $this->assertNull($context->getTenantCode());
        $this->assertEmpty($context->getTenantData());
        $this->assertFalse($context->hasTenant());
    }
}

