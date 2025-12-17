<?php

namespace Metamorphose\Tests\Feature;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Modules\Example\Controller\ExampleController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class ExampleModuleTest extends TestCase
{
    private ExampleController $controller;
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    protected function setUp(): void
    {
        $this->requestContext = new RequestContext();
        $this->tenantContext = new TenantContext();
        $this->unitContext = new UnitContext();
        
        $this->controller = new ExampleController(
            $this->requestContext,
            $this->tenantContext,
            $this->unitContext
        );
    }

    public function testExampleEndpointReturnsCorrectJson(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        
        $request = $requestFactory->createServerRequest('GET', '/example');
        $response = $responseFactory->createResponse();
        
        $result = $this->controller->index($request, $response);
        
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
        
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        
        $this->assertIsArray($data);
        $this->assertEquals('Example', $data['title']);
        $this->assertEquals('Example', $data['modulo']);
        $this->assertEquals('Only example', $data['text']);
    }

    public function testExampleEndpointReturnsValidJson(): void
    {
        $requestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        
        $request = $requestFactory->createServerRequest('GET', '/example');
        $response = $responseFactory->createResponse();
        
        $result = $this->controller->index($request, $response);
        
        $body = (string) $result->getBody();
        $decoded = json_decode($body, true);
        
        $this->assertNotNull($decoded, 'Response should be valid JSON');
        $this->assertIsArray($decoded);
    }
}

