# Arquitetura

Este documento explica a arquitetura do Metamorphose Framework, seus princípios de design e como os componentes interagem.

## Princípios de Design

### 1. Modularidade

O framework é construído em torno de módulos que podem ser conectados ou removidos sem afetar outros módulos. Cada módulo é autocontido e segue a `ModuleInterface`.

### 2. Contextos Explícitos

Em vez de depender de estado global, o framework usa objetos de contexto explícitos:
- `TenantContext`: Informações do tenant atual
- `UnitContext`: Informações da unit atual
- `RequestContext`: Informações da requisição atual

### 3. Sem Mágica Oculta

Todo código é explícito e legível. Não há comportamentos ocultos ou métodos mágicos que tornem o código difícil de entender.

### 4. Agnóstico de Runtime

O framework funciona com:
- PHP-FPM (ciclo tradicional de requisição/resposta)
- Swoole (runtime persistente)
- FrankenPHP (runtime persistente)

Nenhum código depende de estado global mutável que quebraria em runtimes persistentes.

### 5. Conformidade com PSR

O framework segue os padrões PSR:
- PSR-4: Autoloading
- PSR-7: Mensagens HTTP
- PSR-11: Interface de container
- PSR-15: Middleware de servidor HTTP

## Camadas da Arquitetura

```
┌─────────────────────────────────────────┐
│      Requisição HTTP (PSR-7)           │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│      Stack de Middlewares               │
│  - ContextMiddleware                    │
│  - HttpLogMiddleware                    │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│      Handler de Rotas                   │
│  (Controllers dos Módulos)              │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│      Services / Repositories            │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│      Banco de Dados (ConnectionResolver)│
└─────────────────────────────────────────┘
```

## Componentes Principais

### Bootstrap

Localizado em `app/Bootstrap/`, esses arquivos inicializam a aplicação:

- `container.php`: Cria e configura o container PSR-11
- `app.php`: Cria a instância da aplicação Slim
- `middleware.php`: Registra middlewares globais
- `routes.php`: Carrega rotas dos módulos

### Kernel

O kernel (`app/Kernel/`) contém funcionalidades principais do framework:

#### Context (`app/Kernel/Context/`)

- `TenantContext`: Gerencia informações do tenant
- `UnitContext`: Gerencia informações da unit
- `RequestContext`: Gerencia informações da requisição e gera IDs únicos de requisição

#### Module (`app/Kernel/Module/`)

- `ModuleInterface`: Contrato que todos os módulos devem implementar
- `ModuleLoader`: Carrega e inicializa módulos

#### Database (`app/Kernel/Database/`)

- `ConnectionResolverInterface`: Interface para resolver conexões de banco de dados
- `ConnectionResolver`: Resolve conexões baseadas no escopo (core, tenant, unit)

#### Log (`app/Kernel/Log/`)

- `LoggerInterface`: Interface de logger compatível com PSR-3
- `LoggerFactory`: Cria instâncias de logger
- `LogContext`: Enriquece entradas de log com informações de contexto
- `HttpLogMiddleware`: Registra requisições HTTP automaticamente
- `NullLogger`: Descarta logs quando o logging está desabilitado

#### Permission (`app/Kernel/Permission/`)

- `PermissionService`: Valida permissões
- `PermissionResolver`: Resolve escopo de permissão

#### Migration (`app/Kernel/Migration/`)

- `MigrationRunner`: Executa migrações de banco de dados

### Módulos

Módulos (`app/Modules/`) são unidades autocontidas de funcionalidade. Cada módulo:

1. Implementa `ModuleInterface`
2. Registra serviços em `register()`
3. Executa inicializações em `boot()`
4. Define rotas em `routes()`

### CLI

O CLI (`app/CLI/`) fornece ferramentas de linha de comando:

- `KernelCLI`: Ponto de entrada principal do CLI
- `CommandInterface`: Contrato para comandos
- Commands: `ModuleMakeCommand`, `MigrateCommand`

## Fluxo de Requisição

1. **Requisição HTTP chega** em `public/index.php`
2. **Container é construído** via `Bootstrap\buildContainer()`
3. **App Slim é criado** via `Bootstrap\createApp()`
4. **Middlewares são registrados** via `Bootstrap\registerMiddlewares()`
   - `ContextMiddleware` preenche contextos
   - `HttpLogMiddleware` registra requisições (se habilitado)
5. **Rotas são carregadas** via `Bootstrap\loadRoutes()`
   - `ModuleLoader` carrega todos os módulos habilitados
   - Cada módulo registra suas rotas
6. **Requisição é tratada** pelo handler de rota correspondente
7. **Resposta é retornada** e registrada

## Injeção de Dependência

O framework usa PHP-DI para injeção de dependência. Serviços são registrados em:

1. `app/Bootstrap/container.php` - Serviços principais do framework
2. Métodos `register()` dos módulos - Serviços específicos do módulo

Serviços são resolvidos automaticamente via injeção de construtor quando type-hinted.

## Multi-Tenancy

O framework suporta três escopos:

### Core (Global)

- Compartilhado entre todos os tenants
- Usado para dados em todo o sistema
- Resolvido via `ConnectionResolver::resolveCore()`

### Tenant

- Isolado por tenant
- Usado para dados específicos do tenant
- Resolvido via `ConnectionResolver::resolveTenant()`
- Requer que `TenantContext` esteja preenchido

### Unit

- Isolado por unit (sub-tenant)
- Usado para dados específicos da unit
- Resolvido via `ConnectionResolver::resolveUnit()`
- Requer que `UnitContext` esteja preenchido

## Isolamento de Módulos

Módulos são isolados uns dos outros:

- Sem dependências diretas entre módulos
- Comunicação via serviços compartilhados ou eventos (futuro)
- Cada módulo gerencia seus próprios:
  - Controllers
  - Services
  - Repositories
  - Entities
  - Migrations

## Pontos de Extensão

O framework pode ser estendido em vários pontos:

1. **Módulos**: Adicionar nova funcionalidade via módulos
2. **Middlewares**: Adicionar middleware personalizado
3. **Serviços**: Registrar serviços personalizados no container
4. **Comandos**: Adicionar comandos CLI
5. **Handlers de Log**: Personalizar comportamento de logging

## Melhores Práticas

1. **Manter módulos independentes**: Não criar dependências entre módulos
2. **Usar contextos explicitamente**: Sempre injetar objetos de contexto, nunca acessar globais
3. **Registrar serviços adequadamente**: Usar o container para injeção de dependência
4. **Seguir padrões PSR**: Garantir compatibilidade com o ecossistema
5. **Escrever código explícito**: Evitar métodos mágicos e comportamentos ocultos

## Considerações Futuras

A arquitetura foi projetada para suportar:

- Extração de microserviços: Módulos podem ser extraídos para serviços separados
- Sistema de eventos: Para comunicação entre módulos
- Sistema de filas: Para trabalhos em background
- Camada de cache: Para otimização de performance

