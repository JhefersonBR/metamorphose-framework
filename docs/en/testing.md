# Testing

Metamorphose Framework uses PHPUnit for unit and feature testing. This guide will help you write and run tests for your application.

## Configuration

The framework comes with PHPUnit pre-configured. The configuration file is `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">app</directory>
        </include>
    </source>
</phpunit>
```

## Running Tests

### Using CLI Command

The easiest way to run tests is using the CLI command:

```bash
# Run all tests
php bin/metamorphose test

# Run with filter
php bin/metamorphose test --filter=RequestContext

# Run with code coverage
php bin/metamorphose test --coverage

# Run with verbose output
php bin/metamorphose test -v
```

### Using PHPUnit Directly

You can also run PHPUnit directly:

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite Unit

# Run specific test file
vendor/bin/phpunit tests/Unit/Context/RequestContextTest.php

# Run with filter
vendor/bin/phpunit --filter RequestContextTest
```

### Using Composer

```bash
composer test
```

## Test Structure

Tests are organized in the `tests/` directory:

```
tests/
├── Unit/              # Unit tests (isolated components)
│   └── Context/       # Context tests
└── Feature/           # Feature tests (integration tests)
    └── ExampleModuleTest.php
```

## Writing Unit Tests

Unit tests test individual components in isolation. Here's an example:

```php
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
}
```

## Writing Feature Tests

Feature tests test the integration of multiple components. Here's an example:

```php
<?php

namespace Metamorphose\Tests\Feature;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Modules\Example\Controller\ExampleController;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class ExampleModuleTest extends TestCase
{
    private ExampleController $controller;

    protected function setUp(): void
    {
        $requestContext = new RequestContext();
        $tenantContext = new TenantContext();
        $unitContext = new UnitContext();
        
        $this->controller = new ExampleController(
            $requestContext,
            $tenantContext,
            $unitContext
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
        
        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        
        $this->assertEquals('Example', $data['title']);
    }
}
```

## Test Naming Conventions

- Test classes should end with `Test`
- Test methods should start with `test` or use the `@test` annotation
- Use descriptive names: `testRequestIdIsGenerated()`, `testUserCanBeCreated()`

## Assertions

PHPUnit provides many assertion methods:

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Type checking
$this->assertIsString($value);
$this->assertIsArray($value);
$this->assertInstanceOf(ExpectedClass::class, $object);

// Null checks
$this->assertNull($value);
$this->assertNotNull($value);

// Boolean checks
$this->assertTrue($condition);
$this->assertFalse($condition);

// Empty checks
$this->assertEmpty($value);
$this->assertNotEmpty($value);

// Exception testing
$this->expectException(\Exception::class);
$this->expectExceptionMessage('Error message');
```

## Test Setup and Teardown

Use `setUp()` and `tearDown()` methods for test preparation:

```php
protected function setUp(): void
{
    // Called before each test
    $this->context = new RequestContext();
}

protected function tearDown(): void
{
    // Called after each test
    $this->context->clear();
}
```

## Testing Contexts

The framework provides three context classes that can be easily tested:

### RequestContext

```php
public function testRequestContext(): void
{
    $context = new RequestContext();
    
    $this->assertNotEmpty($context->getRequestId());
    $this->assertNull($context->getUserId());
    
    $context->setUserId('user123');
    $this->assertEquals('user123', $context->getUserId());
}
```

### TenantContext

```php
public function testTenantContext(): void
{
    $context = new TenantContext();
    
    $this->assertFalse($context->hasTenant());
    
    $context->setTenantId('tenant123');
    $this->assertTrue($context->hasTenant());
    $this->assertEquals('tenant123', $context->getTenantId());
}
```

### UnitContext

```php
public function testUnitContext(): void
{
    $context = new UnitContext();
    
    $this->assertFalse($context->hasUnit());
    
    $context->setUnitId('unit123');
    $this->assertTrue($context->hasUnit());
    $this->assertEquals('unit123', $context->getUnitId());
}
```

## Testing Controllers

When testing controllers, you need to create mock request and response objects:

```php
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

public function testController(): void
{
    $requestFactory = new ServerRequestFactory();
    $responseFactory = new ResponseFactory();
    
    $request = $requestFactory->createServerRequest('GET', '/example');
    $response = $responseFactory->createResponse();
    
    $result = $this->controller->index($request, $response);
    
    $this->assertInstanceOf(ResponseInterface::class, $result);
}
```

## Code Coverage

Generate code coverage reports:

```bash
# Text coverage report
php bin/metamorphose test --coverage

# HTML coverage report (using PHPUnit directly)
vendor/bin/phpunit --coverage-html coverage/
```

## Best Practices

1. **Test one thing at a time**: Each test should verify one specific behavior
2. **Use descriptive names**: Test names should clearly describe what they test
3. **Keep tests independent**: Tests should not depend on each other
4. **Use setUp/tearDown**: Prepare and clean up test data
5. **Test edge cases**: Don't just test the happy path
6. **Mock external dependencies**: Use mocks for database, APIs, etc.
7. **Keep tests fast**: Unit tests should run quickly
8. **Maintain test coverage**: Aim for high code coverage

## Example Test Suite

Here's a complete example test suite for a module:

```php
<?php

namespace Metamorphose\Tests\Unit\Modules\Example;

use Metamorphose\Modules\Example\Controller\ExampleController;
use PHPUnit\Framework\TestCase;

class ExampleControllerTest extends TestCase
{
    public function testIndexReturnsJson(): void
    {
        // Arrange
        $controller = $this->createController();
        $request = $this->createRequest('GET', '/example');
        $response = $this->createResponse();
        
        // Act
        $result = $controller->index($request, $response);
        
        // Assert
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
        
        $body = json_decode((string) $result->getBody(), true);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('modulo', $body);
        $this->assertArrayHasKey('text', $body);
    }
    
    private function createController(): ExampleController
    {
        // Create controller with dependencies
    }
    
    private function createRequest(string $method, string $uri)
    {
        // Create request
    }
    
    private function createResponse()
    {
        // Create response
    }
}
```

## Troubleshooting

### Tests not found

Make sure your test files are in the correct directory (`tests/Unit` or `tests/Feature`) and follow the naming convention (`*Test.php`).

### Autoloading issues

Run `composer dump-autoload` to regenerate the autoloader.

### PHPUnit not found

Run `composer install` to install PHPUnit and other dependencies.

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPUnit Assertions](https://phpunit.readthedocs.io/en/9.5/assertions.html)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)

