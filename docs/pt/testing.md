# Testes

O Metamorphose Framework usa PHPUnit para testes unitários e de funcionalidade. Este guia ajudará você a escrever e executar testes para sua aplicação.

## Configuração

O framework vem com PHPUnit pré-configurado. O arquivo de configuração é `phpunit.xml`:

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

## Executando Testes

### Usando Comando CLI

A maneira mais fácil de executar testes é usando o comando CLI:

```bash
# Executar todos os testes
php bin/metamorphose test

# Executar com filtro
php bin/metamorphose test --filter=RequestContext

# Executar com cobertura de código
php bin/metamorphose test --coverage

# Executar com saída verbosa
php bin/metamorphose test -v
```

### Usando PHPUnit Diretamente

Você também pode executar o PHPUnit diretamente:

```bash
# Executar todos os testes
vendor/bin/phpunit

# Executar suite de testes específica
vendor/bin/phpunit --testsuite Unit

# Executar arquivo de teste específico
vendor/bin/phpunit tests/Unit/Context/RequestContextTest.php

# Executar com filtro
vendor/bin/phpunit --filter RequestContextTest
```

### Usando Composer

```bash
composer test
```

## Estrutura de Testes

Os testes são organizados no diretório `tests/`:

```
tests/
├── Unit/              # Testes unitários (componentes isolados)
│   └── Context/       # Testes de contexto
└── Feature/           # Testes de funcionalidade (testes de integração)
    └── ExampleModuleTest.php
```

## Escrevendo Testes Unitários

Testes unitários testam componentes individuais isoladamente. Aqui está um exemplo:

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

## Escrevendo Testes de Funcionalidade

Testes de funcionalidade testam a integração de múltiplos componentes. Aqui está um exemplo:

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

## Convenções de Nomenclatura

- Classes de teste devem terminar com `Test`
- Métodos de teste devem começar com `test` ou usar a anotação `@test`
- Use nomes descritivos: `testRequestIdIsGenerated()`, `testUserCanBeCreated()`

## Asserções

O PHPUnit fornece muitos métodos de asserção:

```php
// Igualdade
$this->assertEquals($esperado, $atual);
$this->assertNotEquals($esperado, $atual);

// Verificação de tipo
$this->assertIsString($valor);
$this->assertIsArray($valor);
$this->assertInstanceOf(ClasseEsperada::class, $objeto);

// Verificações de null
$this->assertNull($valor);
$this->assertNotNull($valor);

// Verificações booleanas
$this->assertTrue($condicao);
$this->assertFalse($condicao);

// Verificações de vazio
$this->assertEmpty($valor);
$this->assertNotEmpty($valor);

// Teste de exceções
$this->expectException(\Exception::class);
$this->expectExceptionMessage('Mensagem de erro');
```

## Configuração e Limpeza de Testes

Use os métodos `setUp()` e `tearDown()` para preparação de testes:

```php
protected function setUp(): void
{
    // Chamado antes de cada teste
    $this->context = new RequestContext();
}

protected function tearDown(): void
{
    // Chamado após cada teste
    $this->context->clear();
}
```

## Testando Contextos

O framework fornece três classes de contexto que podem ser facilmente testadas:

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

## Testando Controllers

Ao testar controllers, você precisa criar objetos de request e response mock:

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

## Cobertura de Código

Gere relatórios de cobertura de código:

```bash
# Relatório de cobertura em texto
php bin/metamorphose test --coverage

# Relatório de cobertura HTML (usando PHPUnit diretamente)
vendor/bin/phpunit --coverage-html coverage/
```

## Melhores Práticas

1. **Teste uma coisa por vez**: Cada teste deve verificar um comportamento específico
2. **Use nomes descritivos**: Nomes de testes devem descrever claramente o que testam
3. **Mantenha testes independentes**: Testes não devem depender uns dos outros
4. **Use setUp/tearDown**: Prepare e limpe dados de teste
5. **Teste casos extremos**: Não teste apenas o caminho feliz
6. **Mock dependências externas**: Use mocks para banco de dados, APIs, etc.
7. **Mantenha testes rápidos**: Testes unitários devem executar rapidamente
8. **Mantenha cobertura de testes**: Busque alta cobertura de código

## Exemplo de Suite de Testes

Aqui está um exemplo completo de suite de testes para um módulo:

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
        // Criar controller com dependências
    }
    
    private function createRequest(string $method, string $uri)
    {
        // Criar request
    }
    
    private function createResponse()
    {
        // Criar response
    }
}
```

## Solução de Problemas

### Testes não encontrados

Certifique-se de que seus arquivos de teste estão no diretório correto (`tests/Unit` ou `tests/Feature`) e seguem a convenção de nomenclatura (`*Test.php`).

### Problemas de autoload

Execute `composer dump-autoload` para regenerar o autoloader.

### PHPUnit não encontrado

Execute `composer install` para instalar o PHPUnit e outras dependências.

## Recursos

- [Documentação do PHPUnit](https://phpunit.de/documentation.html)
- [Asserções do PHPUnit](https://phpunit.readthedocs.io/en/9.5/assertions.html)
- [Desenvolvimento Orientado a Testes](https://pt.wikipedia.org/wiki/Test-driven_development)

