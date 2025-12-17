# Microserviços e Execução Remota de Módulos

O Metamorphose Framework foi projetado para suportar tanto arquiteturas monolíticas quanto de microserviços. Você pode facilmente extrair módulos para microserviços separados sem alterar o código do módulo em si.

## Visão Geral

O framework permite que você:
- Execute todos os módulos em uma única aplicação monolítica
- Extraia módulos específicos para rodar como microserviços separados
- Misture módulos locais e remotos de forma transparente
- Migre módulos gradualmente de monólito para microserviços
- **Troque entre local e remoto apenas por configuração, sem mudar código**

## Arquitetura

### Modo Monolítico (Padrão)

Todos os módulos rodam no mesmo processo:

```
┌─────────────────────────────────┐
│     Aplicação Monolítica        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo A │  │ Módulo B │   │
│  └──────────┘  └──────────┘   │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo C │  │ Módulo D │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

### Modo Microserviços

Módulos podem rodar como serviços separados:

```
┌─────────────────────────────────┐
│     Aplicação Principal         │
│  ┌──────────┐  ┌──────────┐   │
│  │ Módulo A │  │ Módulo B │   │
│  │ (Local)  │  │ (Local)  │   │
│  └──────────┘  └──────────┘   │
│         │              │        │
│         ▼              ▼        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Permission│  │ Stock    │   │
│  │ Service   │  │ Service  │   │
│  │ (Remoto)  │  │ (Remoto) │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

## Sistema de Execução de Módulos

O framework utiliza um sistema de **executores de módulos** que permite executar ações de módulos localmente ou remotamente de forma transparente.

### Componentes

#### ModuleExecutorInterface

Interface genérica que define o contrato para execução de módulos:

```php
interface ModuleExecutorInterface
{
    public function execute(string $moduleName, string $action, array $payload = []): mixed;
}
```

#### LocalModuleExecutor

Executa módulos diretamente no mesmo processo (monolítico):

- Resolve o módulo via ModuleLoader
- Chama o método diretamente
- Injeta contextos se necessário
- Executa no mesmo processo

#### RemoteModuleExecutor

Executa módulos configurados como remotos via HTTP:

- Envia requisição HTTP para o microserviço
- Preserva contexto (tenant, unit, request, user)
- Retorna resultado como se fosse execução local
- Trata erros de rede e respostas inválidas

#### ModuleRunner

Facade que decide automaticamente qual executor usar:

- Lê configuração de módulos
- Verifica se módulo é `local` ou `remote`
- Delega para executor adequado
- **Nenhum módulo precisa saber se é local ou remoto**

## Configuração

### Passo 1: Configurar Módulos

Edite `config/modules.php` para definir módulos locais e remotos:

```php
<?php

return [
    'enabled' => [
        // Formato 1: Módulo local (apenas classe)
        \Metamorphose\Modules\Auth\Module::class,
        
        // Formato 2: Módulo local (com configuração explícita)
        [
            'class' => \Metamorphose\Modules\Stock\Module::class,
            'name' => 'stock', // opcional
            'mode' => 'local', // padrão: 'local'
        ],
        
        // Formato 3: Módulo remoto (microserviço)
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30, // opcional, padrão: 30 segundos
            'headers' => [ // opcional, headers customizados
                'X-API-Key' => getenv('PERMISSION_API_KEY'),
            ],
        ],
    ],
];
```

### Passo 2: Variáveis de Ambiente

Configure variáveis de ambiente para URLs dos microserviços:

```bash
# .env ou configuração de ambiente
PERMISSION_SERVICE_URL=http://permission-service:8000
PERMISSION_API_KEY=your-api-key-here
```

## Usando ModuleRunner

### Executando Ações de Módulos

Para executar uma ação de um módulo, use o `ModuleRunner`:

```php
use Metamorphose\Kernel\Module\ModuleRunner;

// No seu controller ou service
$moduleRunner = $container->get(ModuleRunner::class);

// Executar ação localmente ou remotamente (transparente)
$result = $moduleRunner->execute('permission', 'checkPermission', [
    'user_id' => 123,
    'permission' => 'user.create',
]);

// O mesmo código funciona se o módulo for local ou remoto!
```

### Exemplo: Módulo Permission

**Módulo Permission (local ou remoto):**

```php
<?php

namespace Metamorphose\Modules\Permission;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar serviços
    }

    public function boot(): void
    {
        // Inicializações
    }

    public function routes(App $app): void
    {
        // Rotas do módulo
    }

    /**
     * Ação que pode ser executada localmente ou remotamente
     */
    public function checkPermission(array $payload): bool
    {
        $userId = $payload['user_id'];
        $permission = $payload['permission'];
        
        // Lógica de verificação de permissão
        // ...
        
        return true; // ou false
    }
}
```

**Usando o módulo (sem saber se é local ou remoto):**

```php
// Em qualquer controller ou service
$moduleRunner = $container->get(ModuleRunner::class);

$hasPermission = $moduleRunner->execute('permission', 'checkPermission', [
    'user_id' => $currentUser->getId(),
    'permission' => 'product.create',
]);

if (!$hasPermission) {
    throw new \RuntimeException('Permissão negada');
}
```

## Criando um Microserviço

### Passo 1: Criar Entry Point do Microserviço

O framework já fornece o endpoint `/module/execute` que permite executar módulos remotamente. Para criar um microserviço dedicado, você pode:

**Opção A: Usar o mesmo código base (recomendado)**

Crie `public/permission-service.php`:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);
Bootstrap\loadRoutes($app, $container);

// O endpoint /module/execute já está disponível via loadRoutes()
// Ele executará apenas os módulos habilitados neste serviço

$app->run();
```

**Opção B: Entry point customizado**

Se precisar de um entry point específico, você pode criar:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;
use Metamorphose\Kernel\Module\ModuleLoader;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);

// Carregar APENAS o módulo Permission
$moduleClasses = $container->get('config.modules')['enabled'] ?? [];
$loader = new ModuleLoader($container, $app, $moduleClasses);
$loader->load();

// O endpoint /module/execute já está disponível via Bootstrap\loadRoutes()

$app->run();
```

### Passo 2: Configurar Microserviço

Crie `config/modules.php` no microserviço:

```php
<?php

return [
    'enabled' => [
        // Apenas este módulo roda neste microserviço
        \Metamorphose\Modules\Permission\Module::class,
    ],
];
```

### Passo 3: Fazer Deploy do Microserviço

A estrutura do microserviço:

```
permission-service/
├── app/
│   └── Modules/
│       └── Permission/  # Apenas este módulo
├── config/
│   ├── app.php
│   ├── database.php
│   ├── log.php
│   └── modules.php      # Apenas Permission habilitado
├── public/
│   └── index.php         # Entry point do microserviço
└── vendor/
```

## Como Funciona

### Fluxo de Execução Local

1. **Código chama** `ModuleRunner::execute('permission', 'checkPermission', $payload)`
2. **ModuleRunner verifica** configuração: módulo é `local`
3. **LocalModuleExecutor** resolve o módulo via ModuleLoader
4. **Chama método diretamente**: `$module->checkPermission($payload)`
5. **Retorna resultado** diretamente

### Fluxo de Execução Remota

1. **Código chama** `ModuleRunner::execute('permission', 'checkPermission', $payload)`
2. **ModuleRunner verifica** configuração: módulo é `remote`
3. **RemoteModuleExecutor** constrói payload padronizado:
   ```json
   {
     "module": "permission",
     "action": "checkPermission",
     "context": {
       "tenant_id": "123",
       "unit_id": "456",
       "request_id": "abc...",
       "user_id": "789"
     },
     "payload": {
       "user_id": 123,
       "permission": "product.create"
     }
   }
   ```
4. **Envia requisição HTTP POST** para `{endpoint}/module/execute`
5. **Microserviço recebe** requisição em `/module/execute`
6. **ModuleExecuteController** aplica contexto e executa ação localmente
7. **Retorna resposta**:
   ```json
   {
     "success": true,
     "data": true
   }
   ```
8. **RemoteModuleExecutor** decodifica e retorna resultado
9. **Código recebe** resultado como se fosse execução local

### Preservação de Contexto

Todas as informações de contexto são automaticamente preservadas:

- **tenant_id**: Identificador do tenant
- **tenant_code**: Código do tenant
- **unit_id**: Identificador da unit
- **unit_code**: Código da unit
- **request_id**: ID único da requisição
- **user_id**: ID do usuário autenticado

O contexto é enviado no payload e aplicado automaticamente no microserviço.

## Protocolo de Comunicação

### Requisição (Cliente → Microserviço)

```http
POST /module/execute HTTP/1.1
Content-Type: application/json

{
  "module": "permission",
  "action": "checkPermission",
  "context": {
    "tenant_id": "123",
    "tenant_code": "acme",
    "unit_id": "456",
    "unit_code": "warehouse-1",
    "request_id": "abc123...",
    "user_id": "789"
  },
  "payload": {
    "user_id": 123,
    "permission": "product.create"
  }
}
```

### Resposta (Microserviço → Cliente)

**Sucesso:**
```json
{
  "success": true,
  "data": true
}
```

**Erro:**
```json
{
  "success": false,
  "error": "Mensagem de erro"
}
```

## Garantias do Sistema

✅ **Nenhum módulo precisa saber se é local ou remoto**
- O código do módulo é idêntico nos dois cenários
- Apenas a configuração define o modo de execução

✅ **Nenhum controller muda código**
- Controllers usam `ModuleRunner` da mesma forma
- Não há lógica de transporte dentro dos módulos

✅ **Nenhuma regra de negócio muda**
- A lógica do módulo permanece a mesma
- Apenas o transporte muda (local vs HTTP)

✅ **Transparência total**
- O mesmo código funciona em monólito e microserviços
- Migração é feita apenas mudando configuração

## Exemplo Completo: Permission Module

### 1. Módulo Permission (código)

```php
<?php

namespace Metamorphose\Modules\Permission;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar serviços
    }

    public function boot(): void
    {
        // Inicializações
    }

    public function routes(App $app): void
    {
        // Rotas do módulo (se necessário)
    }

    /**
     * Verifica se usuário tem permissão
     * 
     * Esta ação pode ser executada localmente ou remotamente
     */
    public function checkPermission(array $payload): bool
    {
        $userId = $payload['user_id'] ?? null;
        $permission = $payload['permission'] ?? null;
        
        if (!$userId || !$permission) {
            throw new \RuntimeException('user_id e permission são obrigatórios');
        }
        
        // Lógica de verificação de permissão
        // ...
        
        return true; // ou false
    }

    /**
     * Lista permissões do usuário
     */
    public function getUserPermissions(array $payload): array
    {
        $userId = $payload['user_id'] ?? null;
        
        if (!$userId) {
            throw new \RuntimeException('user_id é obrigatório');
        }
        
        // Lógica para buscar permissões
        // ...
        
        return ['user.create', 'user.update', 'product.read'];
    }
}
```

### 2. Configuração - Modo Local

```php
// config/modules.php
return [
    'enabled' => [
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'local',
        ],
    ],
];
```

### 3. Configuração - Modo Remoto

```php
// config/modules.php
return [
    'enabled' => [
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30,
        ],
    ],
];
```

### 4. Usando o Módulo (mesmo código para ambos os modos)

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use Metamorphose\Kernel\Module\ModuleRunner;
use Psr\Container\ContainerInterface;

class ProductController
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function create($request, $response)
    {
        $moduleRunner = $this->container->get(ModuleRunner::class);
        
        // Verificar permissão (funciona local ou remoto)
        $hasPermission = $moduleRunner->execute('permission', 'checkPermission', [
            'user_id' => $currentUser->getId(),
            'permission' => 'product.create',
        ]);
        
        if (!$hasPermission) {
            return $response->withStatus(403)->withJson(['error' => 'Permissão negada']);
        }
        
        // Criar produto...
    }
}
```

## Benefícios

### 1. Transparência de Código

O código do módulo não muda. O mesmo módulo funciona em:
- Modo monolítico
- Modo microserviço
- Modo misto

### 2. Migração Gradual

Migre módulos um de cada vez:
1. Comece com todos os módulos no monólito
2. Extraia um módulo para microserviço
3. Atualize apenas a configuração
4. Repita para outros módulos

### 3. Escalabilidade Independente

Escale microserviços independentemente:
- Módulos de alto tráfego recebem mais recursos
- Módulos de baixo tráfego usam menos recursos
- Conexões de banco de dados são isoladas por serviço

### 4. Flexibilidade Tecnológica

Cada microserviço pode:
- Usar versões diferentes de PHP
- Usar bancos de dados diferentes
- Fazer deploy independentemente
- Ter seu próprio pipeline CI/CD

## Melhores Práticas

### 1. Service Discovery

Use variáveis de ambiente ou service discovery:

```php
'endpoint' => getenv('PERMISSION_SERVICE_URL') 
    ?: 'http://permission-service.' . getenv('KUBERNETES_NAMESPACE') . '.svc.cluster.local',
```

### 2. Health Checks

Implemente endpoints de health check nos microserviços:

```php
$app->get('/health', function ($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 3. Tratamento de Erros

O `RemoteModuleExecutor` trata automaticamente:
- Erros de rede (timeout, conexão recusada)
- Erros de resposta (status code != 200)
- Erros de execução (resposta com success=false)

### 4. Configuração de Timeout

Configure timeouts apropriados:

```php
[
    'mode' => 'remote',
    'endpoint' => 'http://permission-service:8000',
    'timeout' => 30, // segundos
]
```

### 5. Headers Customizados

Adicione headers para autenticação/autorização:

```php
[
    'mode' => 'remote',
    'endpoint' => 'http://permission-service:8000',
    'headers' => [
        'X-API-Key' => getenv('PERMISSION_API_KEY'),
        'Authorization' => 'Bearer ' . getenv('SERVICE_TOKEN'),
    ],
]
```

### 6. Monitoramento

Monitore comunicação entre microserviços:
- Logar todas as requisições remotas
- Rastrear tempos de resposta
- Alertar sobre falhas
- Monitorar saúde dos serviços

## Estratégia de Migração

### Fase 1: Preparação

1. Identificar módulos para extrair
2. Garantir que módulos são autocontidos
3. Verificar que não há dependências diretas entre módulos
4. Testar módulos independentemente

### Fase 2: Extração

1. Criar entry point do microserviço
2. Fazer deploy do microserviço
3. Atualizar configuração da aplicação principal (mudar `mode` para `remote`)
4. Testar integração

### Fase 3: Otimização

1. Otimizar performance do microserviço
2. Implementar cache se necessário
3. Adicionar monitoramento e logging
4. Escalar conforme necessário

## Exemplo: Configuração Completa

### Aplicação Principal (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        // Módulos locais
        \Metamorphose\Modules\Auth\Module::class,
        \Metamorphose\Modules\UserManagement\Module::class,
        
        // Microserviços remotos
        [
            'class' => \Metamorphose\Modules\Permission\Module::class,
            'name' => 'permission',
            'mode' => 'remote',
            'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
            'timeout' => 30,
        ],
        [
            'class' => \Metamorphose\Modules\Stock\Module::class,
            'name' => 'stock',
            'mode' => 'remote',
            'endpoint' => getenv('STOCK_SERVICE_URL') ?: 'http://stock-service:8000',
            'timeout' => 30,
        ],
    ],
];
```

### Serviço de Permission (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        // Apenas este módulo roda neste microserviço
        \Metamorphose\Modules\Permission\Module::class,
    ],
];
```

### Exemplo Docker Compose

```yaml
version: '3.8'

services:
  main-app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - PERMISSION_SERVICE_URL=http://permission-service:8000
      - STOCK_SERVICE_URL=http://stock-service:8000
  
  permission-service:
    build: .
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
  
  stock-service:
    build: .
    ports:
      - "8002:8000"
    environment:
      - APP_ENV=production
```

## Solução de Problemas

### Módulo Não Encontrado

- Verificar se a classe do módulo existe
- Verificar namespace e autoloading
- Garantir que módulo está no diretório correto
- Verificar se módulo está habilitado em `config/modules.php`

### Conexão Recusada

- Verificar se microserviço está rodando
- Verificar se endpoint está correto
- Verificar conectividade de rede
- Revisar regras de firewall

### Contexto Não Preservado

- Garantir que contexto é enviado no payload
- Verificar que `ModuleExecuteController` aplica contexto corretamente
- Verificar ordem dos middlewares
- Revisar tratamento de requisição/resposta

### Timeout

- Aumentar timeout na configuração
- Verificar performance do microserviço
- Verificar latência de rede
- Considerar implementar cache

## Próximos Passos

- Leia sobre [Módulos](modules.md) para desenvolvimento de módulos
- Aprenda sobre [Arquitetura](architecture.md) para design de sistema
- Verifique [Contextos](contexts.md) para gerenciamento de contexto
