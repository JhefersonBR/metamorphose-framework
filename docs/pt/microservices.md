# Microserviços e Módulos Remotos

O Metamorphose Framework foi projetado para suportar tanto arquiteturas monolíticas quanto de microserviços. Você pode facilmente extrair módulos para microserviços separados sem alterar o código do módulo em si.

## Visão Geral

O framework permite que você:
- Execute todos os módulos em uma única aplicação monolítica
- Extraia módulos específicos para rodar como microserviços separados
- Misture módulos locais e remotos de forma transparente
- Migre módulos gradualmente de monólito para microserviços

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
│  └──────────┘  └──────────┘   │
│         │              │        │
│         ▼              ▼        │
│  ┌──────────┐  ┌──────────┐   │
│  │ Product   │  │ Order    │   │
│  │ Service   │  │ Service  │   │
│  │ (Remoto)  │  │ (Remoto) │   │
│  └──────────┘  └──────────┘   │
└─────────────────────────────────┘
```

## Configuração

### Passo 1: Configurar Módulos Remotos

Edite `config/modules.php` para definir módulos remotos:

```php
<?php

return [
    'enabled' => [
        // Módulos locais (rodam no monólito)
        \Metamorphose\Modules\Example\Module::class,
        \Metamorphose\Modules\Auth\Module::class,
        
        // Módulos remotos (microserviços)
        [
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL') ?: 'http://product-service:8000',
            'routes_prefix' => '/products', // Prefixo de rota opcional
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL') ?: 'http://order-service:8000',
            'routes_prefix' => '/orders',
        ],
    ],
];
```

### Passo 2: Variáveis de Ambiente

Configure variáveis de ambiente para URLs dos microserviços:

```bash
# .env ou configuração de ambiente
PRODUCT_SERVICE_URL=http://product-service:8000
ORDER_SERVICE_URL=http://order-service:8000
```

## Criando um Microserviço

### Passo 1: Criar Entry Point do Microserviço

Crie `public/product-service.php` (ou faça deploy em servidor separado):

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);

// Carregar APENAS o módulo ProductCatalog
$loader = new \Metamorphose\Kernel\Module\ModuleLoader(
    $container,
    $app,
    [\Metamorphose\Modules\ProductCatalog\Module::class]
);
$loader->load();

$app->run();
```

### Passo 2: Configurar Microserviço

Crie `config/modules.php` no microserviço:

```php
<?php

return [
    'enabled' => [
        // Apenas este módulo roda neste microserviço
        \Metamorphose\Modules\ProductCatalog\Module::class,
    ],
];
```

### Passo 3: Fazer Deploy do Microserviço

A estrutura do microserviço:

```
product-service/
├── app/
│   └── Modules/
│       └── ProductCatalog/  # Apenas este módulo
├── config/
│   ├── app.php
│   ├── database.php
│   ├── log.php
│   └── modules.php          # Apenas ProductCatalog habilitado
├── public/
│   └── index.php             # Entry point do microserviço
└── vendor/
```

## Como Funciona

### Fluxo de Requisição

1. **Requisição chega** na aplicação principal
2. **Rota corresponde** ao prefixo do módulo remoto (ex: `/products/*`)
3. **Middleware proxy** encaminha requisição para o microserviço
4. **Headers de contexto** (`X-Tenant-ID`, `X-Unit-ID`) são preservados
5. **Resposta** é retornada ao cliente

### Preservação de Contexto

Todas as informações de contexto são automaticamente encaminhadas:

- `X-Tenant-ID`: Identificador do tenant
- `X-Unit-ID`: Identificador da unit
- `X-Tenant-Code`: Código do tenant
- `X-Unit-Code`: Código da unit
- `Authorization`: Tokens de autenticação
- Headers customizados

## Detalhes de Implementação

### Classe RemoteModule

O framework inclui uma classe `RemoteModule` que gerencia o proxy:

```php
<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Slim\App;

class RemoteModule implements ModuleInterface
{
    private string $name;
    private string $baseUrl;
    private ?string $routesPrefix;
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        array $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory
    ) {
        $this->name = $config['name'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->routesPrefix = $config['routes_prefix'] ?? null;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    public function register(ContainerInterface $container): void
    {
        // Nenhum serviço local para registrar
    }

    public function boot(): void
    {
        // Nenhuma inicialização local necessária
    }

    public function routes(App $app): void
    {
        $prefix = $this->routesPrefix ?? '';
        
        // Proxy para todas as rotas do microserviço
        $app->any($prefix . '/{path:.*}', function ($request, $response, $args) {
            // Encaminhar requisição para microserviço
            // Preservar todos os headers e contexto
            // Retornar resposta
        });
    }
}
```

### Melhoria do ModuleLoader

O `ModuleLoader` detecta módulos remotos:

```php
foreach ($moduleConfigs as $moduleConfig) {
    if (is_string($moduleConfig)) {
        // Módulo local
        $this->modules[] = new $moduleConfig();
    } elseif (is_array($moduleConfig) && $moduleConfig['type'] === 'remote') {
        // Módulo remoto
        $this->modules[] = new RemoteModule(
            $moduleConfig,
            $this->container->get(ClientInterface::class),
            $this->container->get(RequestFactoryInterface::class)
        );
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
3. Atualize configuração
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
'base_url' => getenv('PRODUCT_SERVICE_URL') 
    ?: 'http://product-service.' . getenv('KUBERNETES_NAMESPACE') . '.svc.cluster.local',
```

### 2. Health Checks

Implemente endpoints de health check nos microserviços:

```php
$app->get('/health', function ($request, $response) {
    return $response->withJson(['status' => 'ok']);
});
```

### 3. Tratamento de Erros

Trate falhas de microserviços graciosamente:

```php
try {
    $proxyResponse = $this->httpClient->sendRequest($proxyRequest);
} catch (\Exception $e) {
    // Logar erro
    // Retornar resposta de erro apropriada
    return $response->withStatus(503)
        ->withJson(['error' => 'Serviço temporariamente indisponível']);
}
```

### 4. Configuração de Timeout

Configure timeouts apropriados:

```php
$httpClient = new \GuzzleHttp\Client([
    'timeout' => 30,
    'connect_timeout' => 5,
]);
```

### 5. Monitoramento

Monitore comunicação entre microserviços:
- Logar todas as requisições proxy
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
3. Atualizar configuração da aplicação principal
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
            'type' => 'remote',
            'name' => 'ProductCatalog',
            'base_url' => getenv('PRODUCT_SERVICE_URL'),
            'routes_prefix' => '/api/products',
        ],
        [
            'type' => 'remote',
            'name' => 'OrderManagement',
            'base_url' => getenv('ORDER_SERVICE_URL'),
            'routes_prefix' => '/api/orders',
        ],
    ],
];
```

### Serviço de Produtos (`config/modules.php`)

```php
<?php

return [
    'enabled' => [
        \Metamorphose\Modules\ProductCatalog\Module::class,
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
      - PRODUCT_SERVICE_URL=http://product-service:8000
      - ORDER_SERVICE_URL=http://order-service:8000
  
  product-service:
    build: .
    ports:
      - "8001:8000"
    environment:
      - APP_ENV=production
  
  order-service:
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

### Conexão Recusada

- Verificar se microserviço está rodando
- Verificar se base_url está correto
- Verificar conectividade de rede
- Revisar regras de firewall

### Contexto Não Preservado

- Garantir que headers são encaminhados
- Verificar ordem dos middlewares
- Verificar implementação do proxy
- Revisar tratamento de requisição/resposta

## Próximos Passos

- Leia sobre [Módulos](modules.md) para desenvolvimento de módulos
- Aprenda sobre [Arquitetura](architecture.md) para design de sistema
- Verifique [Contextos](contexts.md) para gerenciamento de contexto
```

