# Contextos

O Metamorphose Framework usa objetos de contexto explícitos para gerenciar dados multi-tenant e informações de requisição. Essa abordagem evita estado global e torna o código mais testável e manutenível.

## Visão Geral

O framework fornece três tipos de contexto:

1. **RequestContext**: Informações específicas da requisição
2. **TenantContext**: Informações específicas do tenant
3. **UnitContext**: Informações específicas da unit

Todos os contextos são:
- Explicitamente injetados via injeção de dependência
- Preenchidos via middleware
- Nunca dependem de estado global mutável
- Seguros para runtimes persistentes (Swoole, FrankenPHP)

## RequestContext

O `RequestContext` contém informações sobre a requisição HTTP atual.

### Recursos

- **Geração automática de request ID**: Cada requisição recebe um ID único
- **Rastreamento de user ID**: Armazena o ID do usuário autenticado (quando disponível)
- **Metadados da requisição**: Método, URI e outros dados da requisição

### Uso

```php
use Metamorphose\Kernel\Context\RequestContext;

class MeuController
{
    private RequestContext $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    public function index(...): ResponseInterface
    {
        $requestId = $this->requestContext->getRequestId();
        $userId = $this->requestContext->getUserId();
        
        // Usar request ID para logging, tracing, etc.
    }
}
```

### Métodos

- `getRequestId(): string` - Obter ID único da requisição
- `getUserId(): ?string` - Obter ID do usuário atual (se autenticado)
- `setUserId(?string $userId): void` - Definir user ID
- `getRequestData(): array` - Obter metadados da requisição
- `setRequestData(array $data): void` - Definir metadados da requisição
- `clear(): void` - Resetar contexto (gera novo request ID)

### Formato do Request ID

Request IDs são gerados usando `random_bytes()` e convertidos para hexadecimal:
- Comprimento: 32 caracteres (16 bytes)
- Formato: String hexadecimal
- Exemplo: `a1b2c3d4e5f6789012345678901234ab`

## TenantContext

O `TenantContext` contém informações sobre o tenant atual.

### Preenchimento

O contexto é preenchido via middleware a partir de:
1. Header HTTP `X-Tenant-ID` (preferido)
2. Parâmetro de query `tenant_id` (fallback)
3. Header HTTP `X-Tenant-Code` (opcional)

### Uso

```php
use Metamorphose\Kernel\Context\TenantContext;

class MeuController
{
    private TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function index(...): ResponseInterface
    {
        if (!$this->tenantContext->hasTenant()) {
            // Tratar requisição sem tenant
            return $response->withStatus(400);
        }
        
        $tenantId = $this->tenantContext->getTenantId();
        $tenantCode = $this->tenantContext->getTenantCode();
        
        // Usar dados específicos do tenant
    }
}
```

### Métodos

- `getTenantId(): ?string` - Obter tenant ID
- `setTenantId(?string $tenantId): void` - Definir tenant ID
- `getTenantCode(): ?string` - Obter código do tenant
- `setTenantCode(?string $tenantCode): void` - Definir código do tenant
- `getTenantData(): array` - Obter dados adicionais do tenant
- `setTenantData(array $data): void` - Definir dados adicionais do tenant
- `hasTenant(): bool` - Verificar se tenant está definido
- `clear(): void` - Resetar contexto

### Exemplo de Requisição

```bash
curl -H "X-Tenant-ID: tenant-123" \
     -H "X-Tenant-Code: acme-corp" \
     http://localhost/api/products
```

## UnitContext

O `UnitContext` contém informações sobre a unit atual (sub-tenant).

### Preenchimento

O contexto é preenchido via middleware a partir de:
1. Header HTTP `X-Unit-ID` (preferido)
2. Parâmetro de query `unit_id` (fallback)
3. Header HTTP `X-Unit-Code` (opcional)

### Uso

```php
use Metamorphose\Kernel\Context\UnitContext;

class MeuController
{
    private UnitContext $unitContext;

    public function __construct(UnitContext $unitContext)
    {
        $this->unitContext = $unitContext;
    }

    public function index(...): ResponseInterface
    {
        if (!$this->unitContext->hasUnit()) {
            // Tratar requisição sem unit
            return $response->withStatus(400);
        }
        
        $unitId = $this->unitContext->getUnitId();
        $unitCode = $this->unitContext->getUnitCode();
        
        // Usar dados específicos da unit
    }
}
```

### Métodos

- `getUnitId(): ?string` - Obter unit ID
- `setUnitId(?string $unitId): void` - Definir unit ID
- `getUnitCode(): ?string` - Obter código da unit
- `setUnitCode(?string $unitCode): void` - Definir código da unit
- `getUnitData(): array` - Obter dados adicionais da unit
- `setUnitData(array $data): void` - Definir dados adicionais da unit
- `hasUnit(): bool` - Verificar se unit está definida
- `clear(): void` - Resetar contexto

### Exemplo de Requisição

```bash
curl -H "X-Tenant-ID: tenant-123" \
     -H "X-Unit-ID: unit-456" \
     -H "X-Unit-Code: warehouse-1" \
     http://localhost/api/inventory
```

## Usando Múltiplos Contextos

Você pode usar todos os contextos juntos:

```php
class MeuController
{
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        RequestContext $requestContext,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->requestContext = $requestContext;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function index(...): ResponseInterface
    {
        $data = [
            'request_id' => $this->requestContext->getRequestId(),
            'tenant_id' => $this->tenantContext->getTenantId(),
            'unit_id' => $this->unitContext->getUnitId(),
        ];
        
        // Usar contextos para determinar escopo de dados
    }
}
```

## Middleware de Contexto

Contextos são preenchidos automaticamente pelo `ContextMiddleware` (registrado em `app/Bootstrap/middleware.php`).

O middleware:
1. Extrai IDs de tenant/unit de headers ou parâmetros de query
2. Preenche objetos de contexto
3. Torna contextos disponíveis para todos os handlers

Você não precisa preencher contextos manualmente no seu código.

## Contextos em Logging

Contextos são automaticamente incluídos em entradas de log via `LogContext`:

```php
// Entrada de log automaticamente inclui:
{
    "request_id": "a1b2c3d4...",
    "tenant_id": "tenant-123",
    "unit_id": "unit-456",
    "user_id": "user-789",
    "message": "Produto criado",
    "level": "info"
}
```

## Contextos em Banco de Dados

Contextos são usados pelo `ConnectionResolver` para determinar qual conexão de banco de dados usar:

```php
// Resolve conexão específica do tenant
$connection = $connectionResolver->resolveTenant(
    $tenantContext->getTenantId()
);

// Resolve conexão específica da unit
$connection = $connectionResolver->resolveUnit(
    $unitContext->getUnitId()
);
```

## Testando com Contextos

Em testes, você pode definir valores de contexto manualmente:

```php
$tenantContext = new TenantContext();
$tenantContext->setTenantId('test-tenant-123');

$unitContext = new UnitContext();
$unitContext->setUnitId('test-unit-456');

$requestContext = new RequestContext();
$requestContext->setUserId('test-user-789');
```

## Melhores Práticas

1. **Sempre injetar contextos**: Nunca acessar contextos via estado global
2. **Verificar disponibilidade**: Usar `hasTenant()` e `hasUnit()` antes de usar IDs
3. **Usar contextos explicitamente**: Não passar IDs por aí, passar objetos de contexto
4. **Respeitar escopo de contexto**: Usar contextos de tenant/unit apenas quando apropriado
5. **Limpar contextos em testes**: Resetar contextos entre casos de teste

## Padrões Comuns

### Padrão 1: Endpoint Requerendo Tenant

```php
public function index(...): ResponseInterface
{
    if (!$this->tenantContext->hasTenant()) {
        return $response->withStatus(400)
            ->withJson(['error' => 'Tenant ID obrigatório']);
    }
    
    // Processar requisição específica do tenant
}
```

### Padrão 2: Contexto de Unit Opcional

```php
public function index(...): ResponseInterface
{
    $scope = $this->unitContext->hasUnit() 
        ? 'unit' 
        : 'tenant';
    
    // Usar escopo apropriado
}
```

### Padrão 3: Contexto em Logging

```php
$this->logger->info('Ação realizada', [
    'tenant_id' => $this->tenantContext->getTenantId(),
    'unit_id' => $this->unitContext->getUnitId(),
    'user_id' => $this->requestContext->getUserId(),
]);
```

