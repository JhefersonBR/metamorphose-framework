# Permissões

O Metamorphose Framework inclui um sistema de permissões multi-escopo que valida permissões baseadas no contexto atual (global, tenant ou unit).

## Visão Geral

O sistema de permissões suporta três escopos:

- **Global**: Permissões em todo o sistema
- **Tenant**: Permissões específicas do tenant
- **Unit**: Permissões específicas da unit

## Códigos de Permissão

Códigos de permissão seguem uma convenção de nomenclatura:

- `global:permission_name` - Permissão de escopo global
- `tenant:permission_name` - Permissão de escopo tenant
- `unit:permission_name` - Permissão de escopo unit

Se nenhum prefixo for fornecido, a permissão padrão é escopo global.

## PermissionService

O `PermissionService` valida permissões baseadas no contexto atual.

### Uso

```php
use Metamorphose\Kernel\Permission\PermissionService;

class MeuController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function create(...): ResponseInterface
    {
        if (!$this->permissionService->hasPermission('tenant:products.create')) {
            return $response->withStatus(403)
                ->withJson(['error' => 'Permissão negada']);
        }
        
        // Criar produto
    }
}
```

### Métodos

- `hasPermission(string $permissionCode, ?string $userId = null): bool` - Verificar se permissão é concedida

### Resolução de Permissão

O serviço resolve automaticamente o escopo da permissão:

```php
// Permissão global
$hasPermission = $permissionService->hasPermission('global:users.manage');

// Permissão tenant (usa TenantContext)
$hasPermission = $permissionService->hasPermission('tenant:products.create');

// Permissão unit (usa UnitContext)
$hasPermission = $permissionService->hasPermission('unit:inventory.manage');
```

## PermissionResolver

O `PermissionResolver` determina o escopo de um código de permissão e resolve IDs de contexto.

### Uso

```php
use Metamorphose\Kernel\Permission\PermissionResolver;

$resolver = new PermissionResolver($tenantContext, $unitContext);

$scope = $resolver->resolveScope('tenant:products.create');
// Retorna: 'tenant'

$contextId = $resolver->getContextId('tenant');
// Retorna: tenant ID do TenantContext
```

## Implementação

O sistema de permissões foi projetado para ser estendido. Por padrão, `PermissionService` retorna `false` (negar por padrão) por segurança.

### Estendendo PermissionService

Para implementar verificação real de permissões, estenda `PermissionService` ou crie uma implementação personalizada:

```php
<?php

namespace Metamorphose\Modules\Auth\Service;

use Metamorphose\Kernel\Permission\PermissionService;
use Metamorphose\Kernel\Permission\PermissionResolver;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

class CustomPermissionService extends PermissionService
{
    private PermissionRepository $repository;

    public function __construct(
        PermissionResolver $resolver,
        TenantContext $tenantContext,
        UnitContext $unitContext,
        PermissionRepository $repository
    ) {
        parent::__construct($resolver, $tenantContext, $unitContext);
        $this->repository = $repository;
    }

    protected function checkGlobalPermission(string $permissionCode, ?string $userId): bool
    {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasGlobalPermission($userId, $permissionCode);
    }

    protected function checkTenantPermission(
        string $permissionCode,
        string $tenantId,
        ?string $userId
    ): bool {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasTenantPermission($userId, $tenantId, $permissionCode);
    }

    protected function checkUnitPermission(
        string $permissionCode,
        string $unitId,
        ?string $userId
    ): bool {
        if ($userId === null) {
            return false;
        }
        
        return $this->repository->hasUnitPermission($userId, $unitId, $permissionCode);
    }
}
```

## Middleware de Permissão

Você pode criar middleware para verificar permissões automaticamente:

```php
<?php

namespace Metamorphose\Modules\Auth\Middleware;

use Metamorphose\Kernel\Permission\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PermissionMiddleware implements MiddlewareInterface
{
    private PermissionService $permissionService;
    private string $permissionCode;

    public function __construct(
        PermissionService $permissionService,
        string $permissionCode
    ) {
        $this->permissionService = $permissionService;
        $this->permissionCode = $permissionCode;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $userId = $request->getAttribute('user_id');
        
        if (!$this->permissionService->hasPermission($this->permissionCode, $userId)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Permissão negada']));
            return $response->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }
        
        return $handler->handle($request);
    }
}
```

### Usando Middleware de Permissão

```php
// Em suas rotas de módulo
$app->post('/products', ProductController::class . ':create')
    ->add(new PermissionMiddleware($permissionService, 'tenant:products.create'));
```

## Convenções de Nomenclatura de Permissões

Use uma convenção de nomenclatura consistente para permissões:

```
{scope}:{resource}.{action}
```

Exemplos:
- `global:users.create`
- `global:users.update`
- `global:users.delete`
- `tenant:products.create`
- `tenant:products.update`
- `tenant:orders.view`
- `unit:inventory.manage`
- `unit:reports.view`

## Melhores Práticas

1. **Negar por padrão**: Retornar `false` quando permissão não pode ser determinada
2. **Verificar contexto**: Garantir que contexto de tenant/unit está disponível antes de verificar permissões
3. **Usar códigos explícitos**: Usar códigos de permissão claros e descritivos
4. **Cachear permissões**: Cachear verificações de permissão para performance
5. **Logar negações de permissão**: Logar verificações de permissão falhadas para auditoria de segurança
6. **Usar middleware**: Aplicar permissões via middleware quando possível
7. **Testar permissões**: Escrever testes para lógica de permissão

## Padrões Comuns

### Padrão 1: Verificação de Permissão em Controller

```php
public function create(...): ResponseInterface
{
    $userId = $this->requestContext->getUserId();
    
    if (!$this->permissionService->hasPermission('tenant:products.create', $userId)) {
        return $response->withStatus(403)
            ->withJson(['error' => 'Permissão negada']);
    }
    
    // Criar produto
}
```

### Padrão 2: Verificação de Permissão em Service

```php
public function delete(int $id): void
{
    $userId = $this->requestContext->getUserId();
    
    if (!$this->permissionService->hasPermission('tenant:products.delete', $userId)) {
        throw new PermissionDeniedException('Não é possível deletar produto');
    }
    
    // Deletar produto
}
```

### Padrão 3: Verificação de Múltiplas Permissões

```php
public function update(...): ResponseInterface
{
    $userId = $this->requestContext->getUserId();
    
    $canUpdate = $this->permissionService->hasPermission('tenant:products.update', $userId);
    $canPublish = $this->permissionService->hasPermission('tenant:products.publish', $userId);
    
    if (!$canUpdate) {
        return $response->withStatus(403)
            ->withJson(['error' => 'Não é possível atualizar produto']);
    }
    
    // Atualizar produto
    if ($canPublish) {
        // Publicar produto
    }
}
```

## Considerações de Segurança

1. **Sempre validar permissões**: Nunca pular verificações de permissão
2. **Usar verificações conscientes de contexto**: Garantir que permissões são verificadas no escopo correto
3. **Validar identidade do usuário**: Garantir que user ID está autenticado antes de verificar permissões
4. **Logar eventos de segurança**: Logar negações de permissão e ações relacionadas à segurança
5. **Usar HTTPS**: Sempre usar HTTPS em produção
6. **Rate limiting**: Implementar rate limiting para operações sensíveis

