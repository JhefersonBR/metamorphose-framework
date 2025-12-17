# Metamorphose Framework - Documentação

Metamorphose Framework é um kernel de aplicação PHP modular e multi-tenant baseado em Slim Framework.

## Índice

1. [Instalação](installation.md)
2. [Primeiros Passos](getting-started.md)
3. [Arquitetura](architecture.md)
4. [Módulos](modules.md)
5. [Comandos CLI](cli.md)
6. [Contextos](contexts.md)
7. [Banco de Dados](database.md)
8. [Logs](logging.md)
9. [Permissões](permissions.md)
10. [Microserviços](microservices.md)
11. [Swagger](swagger.md)

## Visão Geral

O Metamorphose Framework foi projetado para ser:

- **Modular**: Módulos plug-and-play que podem ser facilmente adicionados ou removidos
- **Multi-tenant**: Suporta escopos core, tenant e unit
- **Orientado a contexto**: Contextos explícitos para tenant, unit e request
- **Agnóstico de runtime**: Funciona com PHP-FPM, Swoole e FrankenPHP
- **Compatível com PSR**: Segue os padrões PSR-4, PSR-7, PSR-11 e PSR-15
- **Explícito**: Sem mágica oculta, todo código é explícito e legível
- **Pronto para microserviços**: Extraia facilmente módulos para microserviços separados

## Principais Recursos

- Arquitetura modular com módulos plugáveis
- Suporte multi-tenant (core, tenant, unit)
- Gerenciamento explícito de contextos
- Conexões de banco de dados flexíveis por escopo
- Sistema de logs estruturado com middleware HTTP
- Sistema de permissões multi-escopo
- CLI integrado para criação de módulos e migrações
- Sem estado global mutável
- Compatível com runtimes persistentes
- **Suporte a microserviços**: Execute módulos como serviços separados

## Requisitos

- PHP >= 8.1
- Composer
- MySQL/MariaDB (ou banco de dados compatível)

## Início Rápido

```bash
# Instalar dependências
composer install

# Criar um novo módulo
php bin/metamorphose module:make MeuModulo

# Executar migrações
php bin/metamorphose migrate --scope=core
```

## Estrutura da Documentação

Cada seção da documentação cobre um aspecto específico do framework:

- **Instalação**: Como configurar o framework
- **Primeiros Passos**: Seus primeiros passos com o framework
- **Arquitetura**: Entendendo a arquitetura do framework
- **Módulos**: Criando e gerenciando módulos
- **Comandos CLI**: Usando a interface de linha de comando
- **Contextos**: Trabalhando com contextos de tenant, unit e request
- **Banco de Dados**: Conexões de banco de dados e migrações
- **Logs**: Configurando e usando o sistema de logs
- **Permissões**: Implementando verificações de permissão
- **Microserviços**: Extraindo módulos para microserviços separados

