<?php

namespace Metamorphose\Tests\Unit\Context;

use Metamorphose\Kernel\Context\RequestContext;
use PHPUnit\Framework\TestCase;

class RequestContextTest extends TestCase
{
    public function testRequestIdIsGenerated(): void
    {
        $context = new RequestContext();
        
        $requestId = $context->getRequestId();
        
        $this->assertNotEmpty($requestId);
        $this->assertIsString($requestId);
        $this->assertEquals(32, strlen($requestId)); // 16 bytes = 32 hex chars
    }

    public function testRequestIdIsUnique(): void
    {
        $context1 = new RequestContext();
        $context2 = new RequestContext();
        
        $this->assertNotEquals(
            $context1->getRequestId(),
            $context2->getRequestId()
        );
    }

    public function testUserIdCanBeSetAndRetrieved(): void
    {
        $context = new RequestContext();
        
        $this->assertNull($context->getUserId());
        
        $context->setUserId('user123');
        
        $this->assertEquals('user123', $context->getUserId());
    }

    public function testRequestDataCanBeSetAndRetrieved(): void
    {
        $context = new RequestContext();
        
        $data = ['key' => 'value', 'number' => 42];
        $context->setRequestData($data);
        
        $this->assertEquals($data, $context->getRequestData());
    }

    public function testClearResetsContext(): void
    {
        $context = new RequestContext();
        $originalRequestId = $context->getRequestId();
        
        $context->setUserId('user123');
        $context->setRequestData(['key' => 'value']);
        
        $context->clear();
        
        $this->assertNotEquals($originalRequestId, $context->getRequestId());
        $this->assertNull($context->getUserId());
        $this->assertEmpty($context->getRequestData());
    }
}

