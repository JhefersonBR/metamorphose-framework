# Installation Guide

This guide will help you install and configure the Metamorphose Framework.

## Requirements

- PHP >= 8.1
- Composer
- MySQL/MariaDB 5.7+ (or compatible database)
- Web server (Apache, Nginx, or PHP built-in server)
- Optional: Swoole or FrankenPHP for persistent runtimes

## Step 1: Install Dependencies

```bash
composer install
```

This will install all required dependencies including:
- Slim Framework
- PHP-DI (Dependency Injection Container)
- Monolog (Logging)
- FluentPDO (Database abstraction)

## Step 2: Configure Environment

Copy the example environment file (if available) or configure the following environment variables:

```bash
# Application
APP_ENV=production
APP_DEBUG=false

# Database - Core
DB_CORE_HOST=localhost
DB_CORE_PORT=3306
DB_CORE_DATABASE=metamorphose_core
DB_CORE_USERNAME=root
DB_CORE_PASSWORD=your_password

# Database - Tenant
DB_TENANT_HOST=localhost
DB_TENANT_PORT=3306
DB_TENANT_DATABASE=metamorphose_tenant
DB_TENANT_USERNAME=root
DB_TENANT_PASSWORD=your_password

# Database - Unit
DB_UNIT_HOST=localhost
DB_UNIT_PORT=3306
DB_UNIT_DATABASE=metamorphose_unit
DB_UNIT_USERNAME=root
DB_UNIT_PASSWORD=your_password

# Logging
LOG_ENABLED=true
LOG_CHANNEL=metamorphose
LOG_LEVEL=info
LOG_PATH=storage/logs
HTTP_LOG_ENABLED=true
```

Alternatively, you can edit the configuration files directly in the `/config` directory:

- `config/app.php` - Application settings
- `config/database.php` - Database connections
- `config/log.php` - Logging configuration
- `config/modules.php` - Enabled modules

## Step 3: Create Database Schemas

Create the database schemas for each scope:

```sql
CREATE DATABASE metamorphose_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE metamorphose_tenant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE metamorphose_unit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Step 4: Run Migrations

Run migrations for each scope:

```bash
php bin/metamorphose migrate --scope=core
php bin/metamorphose migrate --scope=tenant
php bin/metamorphose migrate --scope=unit
```

## Step 5: Configure Web Server

### Apache

Ensure mod_rewrite is enabled and configure your virtual host:

```apache
<VirtualHost *:80>
    ServerName metamorphose.local
    DocumentRoot /path/to/metamorphose-framework/public
    
    <Directory /path/to/metamorphose-framework/public>
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
    root /path/to/metamorphose-framework/public;
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

### PHP Built-in Server (Development)

```bash
php -S localhost:8000 -t public
```

## Step 6: Verify Installation

Visit `http://localhost/example` (or your configured domain) to see the Example module in action.

You should see a JSON response with:
- A welcome message
- Request ID
- Tenant ID (if provided)
- Unit ID (if provided)

## Troubleshooting

### Permission Errors

Ensure the storage directory has write permissions:

```bash
chmod -R 775 storage
```

### Database Connection Errors

- Verify database credentials in `config/database.php`
- Ensure databases exist
- Check database user permissions

### Module Not Found Errors

- Verify modules are listed in `config/modules.php`
- Check module class names match exactly
- Ensure autoloader is working: `composer dump-autoload`

## Next Steps

- Read the [Getting Started Guide](getting-started.md)
- Learn about [Architecture](architecture.md)
- Create your first [Module](modules.md)

