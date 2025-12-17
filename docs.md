Você é um arquiteto de software PHP sênior.
Quero que você gere a base completa de um projeto PHP chamado
**Metamorphose Framework**.

Este projeto é um KERNEL DE APLICAÇÃO em PHP, baseado em Slim Framework,
com arquitetura modular, multi-tenant e orientada a contexto.

⚠️ REGRAS OBRIGATÓRIAS (NÃO IGNORAR)
- NÃO usar Laravel ou qualquer componente do Laravel
- NÃO usar frameworks full-stack
- NÃO usar static com estado mutável
- Código deve funcionar corretamente em PHP-FPM
- Código deve ser compatível com runtimes persistentes (Swoole / FrankenPHP)
- Usar PSR-4, PSR-7, PSR-11, PSR-15
- Usar Composer (composer.json ÚNICO na raiz)
- Slim deve ser usado apenas como roteador e middleware
- Nenhuma lógica “mágica” ou escondida
- Código explícito, legível e organizado
- CLI deve fazer parte do MESMO projeto
- NÃO gerar explicações fora do código
- NÃO simplificar a arquitetura

=====================================================
OBJETIVO DO PROJETO
=====================================================

Criar o **Metamorphose Framework**, um kernel de aplicação PHP com:

- Monólito modular
- Módulos plugáveis
- Arquitetura preparada para extração futura de microserviços
- Multi-tenant híbrido:
  - core (global)
  - tenant
  - unit
- Contextos explícitos:
  - TenantContext
  - UnitContext
  - RequestContext
- Persistência flexível usando FluentPDO
- Sistema de permissões multi-escopo
- Camada de logs transversal baseada em middleware
- CLI próprio (inspirado no Artisan, porém simples e focado)
- Sem acoplamento entre módulos
- Sem dependência direta de runtime específico

=====================================================
ESTRUTURA DE PASTAS (OBRIGATÓRIA)
=====================================================

/app
  /Bootstrap
    app.php
    container.php
    middleware.php
    routes.php

  /Kernel
    /Context
      TenantContext.php
      UnitContext.php
      RequestContext.php

    /Module
      ModuleInterface.php
      ModuleLoader.php

    /Database
      ConnectionResolverInterface.php
      ConnectionResolver.php

    /Log
      LoggerInterface.php
      LoggerFactory.php
      LogContext.php
      HttpLogMiddleware.php
      NullLogger.php

    /Permission
      PermissionService.php
      PermissionResolver.php

    /Migration
      MigrationRunner.php

  /Modules
    /Example
      Module.php
      Routes.php
      config.php
      /Controller
      /Service
      /Repository
      /Entity
      /Migrations
        /core
        /tenant
        /unit

  /Shared
    /Config
    /Logger
    /Utils

  /CLI
    KernelCLI.php
    CommandInterface.php
    /Commands
      ModuleMakeCommand.php
      MigrateCommand.php

/config
  app.php
  modules.php
  database.php
  log.php

/bin
  metamorphose   (CLI principal executável)
  migrate        (atalho opcional)

/public
  index.php

=====================================================
NAMESPACE PADRÃO
=====================================================

Todo o código deve usar o namespace base:

Metamorphose\

Exemplos:
- Metamorphose\Kernel\Context\TenantContext
- Metamorphose\Modules\Example\ExampleModule
- Metamorphose\CLI\KernelCLI

=====================================================
DETALHES TÉCNICOS OBRIGATÓRIOS
=====================================================

1) CONTEXTOS
- TenantContext, UnitContext e RequestContext devem ser explícitos
- Contextos devem ser preenchidos via middleware
- RequestContext deve gerar request_id único por request
- Nenhum contexto deve depender de estado global persistente

2) MÓDULOS
- Cada módulo implementa ModuleInterface
- ModuleInterface deve conter:
  - register(Container $container)
  - boot()
  - routes(App $app)
- ModuleLoader carrega os módulos definidos em config/modules.php
- Módulos NÃO sabem se estão rodando em monólito ou microserviço

3) LOGS
- Logs devem ser opcionais via config/log.php
- Deve existir HttpLogMiddleware
- Logs devem ser estruturados
- LogContext deve incluir automaticamente:
  - request_id
  - tenant_id
  - unit_id
  - user_id (quando existir)
- LoggerFactory decide se usa logger real ou NullLogger

4) PERSISTÊNCIA
- Usar FluentPDO
- ConnectionResolver deve suportar:
  - core
  - tenant
  - unit
- Resolver deve usar TenantContext e UnitContext
- Repositórios definem explicitamente o escopo de conexão

5) PERMISSÕES
- Sistema simples baseado em:
  - permission code (string)
  - escopo: global | tenant | unit
- PermissionService valida permissões com base no contexto atual

6) CLI (MUITO IMPORTANTE)
- CLI faz parte do mesmo projeto
- Arquivo de entrada: bin/metamorphose
- KernelCLI registra comandos
- CommandInterface define:
  - name()
  - description()
  - handle(array $args)
- Criar pelo menos os comandos:
  - module:make {Name}
  - migrate --scope=core|tenant|unit
- module:make deve gerar toda a estrutura de um módulo

7) BOOTSTRAP HTTP
- public/index.php deve:
  - carregar autoload do Composer
  - criar container PSR-11
  - inicializar Slim
  - registrar middlewares
  - carregar módulos via ModuleLoader
  - executar a aplicação

=====================================================
EXPECTATIVA FINAL
=====================================================

Gere:

- Código funcional
- Projeto completo
- Estrutura organizada
- Comentários explicando decisões importantes
- Um módulo Example funcionando
- CLI funcionando com module:make
- HttpLogMiddleware já integrado (ativável/desativável via config)
- composer.json na raiz do projeto
- Autoload PSR-4 configurado corretamente

Não gere explicações fora do código.
Não simplifique a arquitetura.
Não omita arquivos importantes.

Gere o projeto completo do **Metamorphose Framework**.
