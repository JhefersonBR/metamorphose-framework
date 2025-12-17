# Metamorphose Framework

Kernel de aplicação PHP modular e multi-tenant baseado em Slim Framework.

## Requisitos

- PHP >= 8.1
- Composer

## Instalação

```bash
composer install
```

## Estrutura

- `/app` - Código da aplicação
- `/config` - Arquivos de configuração
- `/public` - Ponto de entrada HTTP
- `/bin` - Comandos CLI

## Uso

### HTTP

O servidor HTTP é iniciado através de `public/index.php`.

### CLI

```bash
php bin/metamorphose module:make NomeDoModulo
php bin/metamorphose migrate --scope=core
```

## Configuração

Configure as variáveis de ambiente ou edite os arquivos em `/config`:

- `config/app.php` - Configurações da aplicação
- `config/database.php` - Configurações de banco de dados
- `config/log.php` - Configurações de log
- `config/modules.php` - Módulos habilitados

