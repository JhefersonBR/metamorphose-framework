# Logs

O Metamorphose Framework inclui um sistema abrangente de logging com registro automático de requisições HTTP e enriquecimento de contexto.

## Configuração

O logging é configurado em `config/log.php`:

```php
<?php

return [
    'enabled' => filter_var(getenv('LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
    'channel' => getenv('LOG_CHANNEL') ?: 'metamorphose',
    'level' => getenv('LOG_LEVEL') ?: 'info',
    'path' => getenv('LOG_PATH') ?: __DIR__ . '/../storage/logs',
    'http_log' => filter_var(getenv('HTTP_LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
];
```

### Opções

- `enabled`: Habilitar/desabilitar logging (usa NullLogger quando desabilitado)
- `channel`: Nome do canal de log
- `level`: Nível mínimo de log (debug, info, notice, warning, error, critical, alert, emergency)
- `path`: Diretório para arquivos de log
- `http_log`: Habilitar/desabilitar registro automático de requisições HTTP

## Usando o Logger

### Em Controllers

```php
use Metamorphose\Kernel\Log\LoggerInterface;

class MeuController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function index(...): ResponseInterface
    {
        $this->logger->info('Produtos listados', [
            'count' => 10,
            'filters' => ['category' => 'electronics'],
        ]);
        
        // ...
    }
}
```

### Em Services

```php
use Metamorphose\Kernel\Log\LoggerInterface;

class ProductService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(array $data): void
    {
        try {
            // Criar produto
            $this->logger->info('Produto criado', ['product_id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Falha ao criar produto', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
```

## Níveis de Log

O framework suporta todos os níveis de log PSR-3:

```php
$logger->debug('Informações detalhadas de debug');
$logger->info('Mensagens informativas');
$logger->notice('Eventos normais mas significativos');
$logger->warning('Mensagens de aviso');
$logger->error('Mensagens de erro');
$logger->critical('Condições críticas');
$logger->alert('Ação deve ser tomada imediatamente');
$logger->emergency('Sistema está inutilizável');
```

## Enriquecimento de Contexto

Entradas de log são automaticamente enriquecidas com informações de contexto via `LogContext`:

```php
// Sua entrada de log
$logger->info('Produto criado', ['product_id' => 123]);

// Entrada de log real inclui:
{
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": "tenant-123",
    "unit_id": "unit-456",
    "user_id": "user-789",
    "message": "Produto criado",
    "product_id": 123,
    "level": "info",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Campos de Contexto

- `request_id`: Identificador único da requisição
- `tenant_id`: ID do tenant atual (se disponível)
- `unit_id`: ID da unit atual (se disponível)
- `user_id`: ID do usuário atual (se disponível)

## Logging de Requisições HTTP

O `HttpLogMiddleware` registra automaticamente todas as requisições HTTP:

### O Que É Registrado

- Método HTTP (GET, POST, PUT, DELETE, etc.)
- URI da requisição
- Código de status da resposta
- Tempo de resposta (em milissegundos)
- Endereço IP do cliente
- Informações de contexto (request_id, tenant_id, unit_id, user_id)

### Nível de Log Baseado em Código de Status

- `info`: Códigos de status 200-399
- `warning`: Códigos de status 400-499
- `error`: Códigos de status 500+

### Exemplo de Entrada de Log

```json
{
    "request_id": "a1b2c3d4e5f6...",
    "tenant_id": "tenant-123",
    "unit_id": null,
    "user_id": "user-789",
    "method": "POST",
    "uri": "/api/products",
    "status_code": 201,
    "duration_ms": 45.23,
    "ip": "192.168.1.100",
    "level": "info",
    "message": "HTTP Request",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

### Desabilitando Logging HTTP

Defina `http_log` como `false` em `config/log.php`:

```php
'http_log' => false,
```

Ou via variável de ambiente:

```bash
HTTP_LOG_ENABLED=false
```

## Arquivos de Log

Logs são escritos em arquivos no caminho configurado:

```
storage/logs/
├── 2024-01-15.log
├── 2024-01-16.log
└── 2024-01-17.log
```

Arquivos são nomeados por data (YYYY-MM-DD.log).

## Desabilitando Logging

Quando o logging está desabilitado, o framework usa `NullLogger`, que descarta todas as mensagens de log:

```php
'enabled' => false,
```

Isso é útil para:
- Ambientes de desenvolvimento onde você não precisa de logs
- Testes de performance
- Reduzir operações de I/O

## Handlers de Log Personalizados

O framework usa Monolog. Você pode estender o logging registrando handlers personalizados em `LoggerFactory`:

```php
// Em app/Kernel/Log/LoggerFactory.php
$logger->pushHandler(new CustomHandler());
```

## Melhores Práticas

1. **Usar níveis apropriados**: Não logar tudo como info
2. **Incluir contexto**: Adicionar dados relevantes às entradas de log
3. **Não logar dados sensíveis**: Evitar logar senhas, tokens, etc.
4. **Usar logging estruturado**: Passar arrays para melhor parsing
5. **Logar erros com contexto**: Incluir informações suficientes para debug
6. **Monitorar tamanho de log**: Rotacionar logs regularmente
7. **Usar logging HTTP**: Habilitar logging HTTP para monitoramento de API

## Padrões Comuns

### Padrão 1: Logging com Contexto

```php
$this->logger->info('Ação realizada', [
    'action' => 'product_created',
    'product_id' => $id,
    'tenant_id' => $this->tenantContext->getTenantId(),
]);
```

### Padrão 2: Logging de Erro

```php
try {
    // Operação
} catch (\Exception $e) {
    $this->logger->error('Operação falhou', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'context' => $contextData,
    ]);
    throw $e;
}
```

### Padrão 3: Logging de Debug

```php
if ($this->config['debug']) {
    $this->logger->debug('Informações de debug', [
        'variable' => $value,
        'state' => $state,
    ]);
}
```

## Rotação de Logs

Para produção, considere usar ferramentas de rotação de logs:

- **logrotate** (Linux): Rotacionar logs baseado em tamanho ou tempo
- **Monolog RotatingFileHandler**: Suporte de rotação integrado

Exemplo com Monolog:

```php
use Monolog\Handler\RotatingFileHandler;

$handler = new RotatingFileHandler(
    $logPath . '/app.log',
    30, // Manter 30 dias
    Logger::INFO
);
```

