<?php

namespace Metamorphose\Tests\Feature;

use Metamorphose\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

// Include Bootstrap functions
require_once __DIR__ . '/../../app/Bootstrap/container.php';
require_once __DIR__ . '/../../app/Bootstrap/app.php';
require_once __DIR__ . '/../../app/Bootstrap/middleware.php';
require_once __DIR__ . '/../../app/Bootstrap/routes.php';

class ExampleRouteTest extends TestCase
{
    private App $app;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        // Build container
        $this->container = Bootstrap\buildContainer();
        
        // Create app
        $this->app = Bootstrap\createApp($this->container);
        
        // Register middlewares
        Bootstrap\registerMiddlewares($this->app, $this->container);
        
        // Load routes
        Bootstrap\loadRoutes($this->app, $this->container);
    }

    public function testExampleRouteReturns200(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/example');
        
        $response = $this->app->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testExampleRouteReturnsJsonContentType(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/example');
        
        $response = $this->app->handle($request);
        
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testExampleRouteReturnsCorrectJsonStructure(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/example');
        
        $response = $this->app->handle($request);
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('modulo', $data);
        $this->assertArrayHasKey('text', $data);
    }

    public function testExampleRouteReturnsCorrectJsonValues(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/example');
        
        $response = $this->app->handle($request);
        
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        $this->assertEquals('Example', $data['title']);
        $this->assertEquals('Example', $data['modulo']);
        $this->assertEquals('Only example', $data['text']);
    }

    public function testExampleRouteReturnsValidJson(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/example');
        
        $response = $this->app->handle($request);
        
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        
        $this->assertNotNull($decoded, 'Response should be valid JSON');
        $this->assertIsArray($decoded);
    }

    public function testExampleRouteOnlyAcceptsGetMethod(): void
    {
        $requestFactory = new ServerRequestFactory();
        
        // Test POST method (should return 405 Method Not Allowed)
        $postRequest = $requestFactory->createServerRequest('POST', '/example');
        $postResponse = $this->app->handle($postRequest);
        
        // Should return 405 Method Not Allowed
        $this->assertEquals(405, $postResponse->getStatusCode());
    }
}

