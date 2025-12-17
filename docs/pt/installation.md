# Guia de Instalação

Este guia ajudará você a instalar e configurar o Metamorphose Framework.

## Requisitos

- PHP >= 8.1
- Composer
- Banco de dados (um dos seguintes):
  - **SQLite** (incluído no PHP, ideal para desenvolvimento)
  - **MySQL 5.7+** ou **MariaDB 10.2+** (requer extensão PDO MySQL)
  - **PostgreSQL 9.5+** (requer extensão PDO PostgreSQL)
  - **SQL Server 2012+** (requer extensão PDO SQL Server)
  - **Oracle 11g+** (requer extensão OCI8)
- Servidor web (Apache, Nginx ou servidor PHP built-in)
- Opcional: Swoole ou FrankenPHP para runtimes persistentes

### Extensões PHP Necessárias

Para usar bancos de dados específicos, você precisa das extensões PDO correspondentes:

- **SQLite**: `pdo_sqlite` (geralmente incluído)
- **MySQL/MariaDB**: `pdo_mysql`
- **PostgreSQL**: `pdo_pgsql`
- **SQL Server**: `pdo_sqlsrv` (requer Microsoft ODBC Driver)
- **Oracle**: `oci8` ou `pdo_oci`

## Passo 1: Instalar Dependências

```bash
composer install
```

Isso instalará todas as dependências necessárias, incluindo:
- Slim Framework
- PHP-DI (Container de Injeção de Dependência)
- Monolog (Logging)
- Doctrine DBAL (Abstração de banco de dados)

## Passo 2: Configurar Ambiente

Copie o arquivo de exemplo de ambiente (se disponível) ou configure as seguintes variáveis de ambiente:

```bash
# Aplicação
APP_ENV=production
APP_DEBUG=false

# Banco de Dados - Core
DB_CORE_HOST=localhost
DB_CORE_PORT=3306
DB_CORE_DATABASE=metamorphose_core
DB_CORE_USERNAME=root
DB_CORE_PASSWORD=sua_senha

# Banco de Dados - Tenant
DB_TENANT_HOST=localhost
DB_TENANT_PORT=3306
DB_TENANT_DATABASE=metamorphose_tenant
DB_TENANT_USERNAME=root
DB_TENANT_PASSWORD=sua_senha

# Banco de Dados - Unit
DB_UNIT_HOST=localhost
DB_UNIT_PORT=3306
DB_UNIT_DATABASE=metamorphose_unit
DB_UNIT_USERNAME=root
DB_UNIT_PASSWORD=sua_senha

# Logs
LOG_ENABLED=true
LOG_CHANNEL=metamorphose
LOG_LEVEL=info
LOG_PATH=storage/logs
HTTP_LOG_ENABLED=true
```

Alternativamente, você pode editar os arquivos de configuração diretamente no diretório `/config`:

- `config/app.php` - Configurações da aplicação
- `config/database.php` - Conexões de banco de dados
- `config/log.php` - Configuração de logs
- `config/modules.php` - Módulos habilitados

## Passo 3: Criar Schemas de Banco de Dados

Crie os schemas de banco de dados para cada escopo:

```sql
CREATE DATABASE metamorphose_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE metamorphose_tenant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE metamorphose_unit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Passo 4: Executar Migrações

Execute as migrações para cada escopo:

```bash
php bin/metamorphose migrate --scope=core
php bin/metamorphose migrate --scope=tenant
php bin/metamorphose migrate --scope=unit
```

## Passo 5: Configurar Servidor Web

### Apache

Certifique-se de que o mod_rewrite está habilitado e configure seu virtual host:

```apache
<VirtualHost *:80>
    ServerName metamorphose.local
    DocumentRoot /caminho/para/metamorphose-framework/public
    
    <Directory /caminho/para/metamorphose-framework/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name metamorphose.local;
    root /caminho/para/metamorphose-framework/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Servidor PHP Built-in (Desenvolvimento)

```bash
php -S localhost:8000 -t public
```

## Passo 6: Verificar Instalação

Visite `http://localhost/example` (ou seu domínio configurado) para ver o módulo Example em ação.

Você deve ver uma resposta JSON com:
- Uma mensagem de boas-vindas
- Request ID
- Tenant ID (se fornecido)
- Unit ID (se fornecido)

## Solução de Problemas

### Erros de Permissão

Certifique-se de que o diretório storage tem permissões de escrita:

```bash
chmod -R 775 storage
```

### Erros de Conexão com Banco de Dados

- Verifique as credenciais do banco de dados em `config/database.php`
- Certifique-se de que os bancos de dados existem
- Verifique as permissões do usuário do banco de dados

### Erros de Módulo Não Encontrado

- Verifique se os módulos estão listados em `config/modules.php`
- Verifique se os nomes das classes dos módulos correspondem exatamente
- Certifique-se de que o autoloader está funcionando: `composer dump-autoload`

## Próximos Passos

- Leia o [Guia de Primeiros Passos](getting-started.md)
- Aprenda sobre [Arquitetura](architecture.md)
- Crie seu primeiro [Módulo](modules.md)

