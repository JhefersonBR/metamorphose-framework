# CLI Commands

Metamorphose Framework includes a command-line interface (CLI) for common tasks like creating modules and running migrations.

## Usage

```bash
php bin/metamorphose [command] [options]
```

Or use the shortcut:

```bash
php bin/migrate [options]  # Shortcut for migrate command
```

## Available Commands

### module:make

Creates a new module with the complete directory structure.

**Usage:**
```bash
php bin/metamorphose module:make ModuleName
```

**Example:**
```bash
php bin/metamorphose module:make ProductCatalog
```

**What it creates:**
- `app/Modules/ProductCatalog/Module.php` - Main module class
- `app/Modules/ProductCatalog/Routes.php` - Routes file
- `app/Modules/ProductCatalog/config.php` - Module configuration
- `app/Modules/ProductCatalog/Controller/ProductCatalogController.php` - Example controller
- `app/Modules/ProductCatalog/Service/` - Service directory
- `app/Modules/ProductCatalog/Repository/` - Repository directory
- `app/Modules/ProductCatalog/Entity/` - Entity directory
- `app/Modules/ProductCatalog/Migrations/core/` - Core migrations directory
- `app/Modules/ProductCatalog/Migrations/tenant/` - Tenant migrations directory
- `app/Modules/ProductCatalog/Migrations/unit/` - Unit migrations directory

**After creation:**
1. Register the module in `config/modules.php`
2. Implement your business logic
3. Create migrations if needed
4. Test your module

### serve

Starts a local PHP development server with the application and Swagger UI.

**Usage:**
```bash
php bin/metamorphose serve [options]
```

**Options:**
- `--host=HOST` - Server host (default: localhost)
- `--port=PORT` - Server port (default: 8000)
- `--no-swagger` - Don't generate Swagger documentation before starting

**Examples:**
```bash
# Start server on default port (8000)
php bin/metamorphose serve

# Start server on custom port
php bin/metamorphose serve --port=8080

# Start server on custom host and port
php bin/metamorphose serve --host=0.0.0.0 --port=8080

# Start without generating Swagger
php bin/metamorphose serve --no-swagger
```

**What it does:**
1. Generates Swagger documentation (unless `--no-swagger` is used)
2. Starts PHP built-in server
3. Serves application from `public/` directory
4. Shows URLs for:
   - Application: `http://localhost:8000`
   - Swagger UI: `http://localhost:8000/swagger-ui`
   - Swagger JSON: `http://localhost:8000/swagger.json`

**Note:** Press `Ctrl+C` to stop the server.

### test

Runs unit and feature tests using PHPUnit.

**Usage:**
```bash
php bin/metamorphose test [options]
```

**Options:**
- `--filter=FILTER` - Filter tests by name pattern
- `--coverage` - Generate code coverage report
- `-v` or `--verbose` - Verbose output
- `--stop-on-failure` - Stop execution on first failure

**Examples:**
```bash
# Run all tests
php bin/metamorphose test

# Run tests matching a filter
php bin/metamorphose test --filter=RequestContext

# Run with code coverage
php bin/metamorphose test --coverage

# Run with verbose output
php bin/metamorphose test -v

# Stop on first failure
php bin/metamorphose test --stop-on-failure
```

**Alternative:**
You can also use PHPUnit directly:
```bash
vendor/bin/phpunit
composer test
```

### log:clear

Removes old log files or all logs from the system.

**Usage:**
```bash
php bin/metamorphose log:clear [options]
```

**Options:**
- `--days=N` - Remove logs older than N days (default: 7 days)
- `--all` - Remove all log files
- `-y` or `--yes` - Auto-confirm (don't ask)

**Examples:**
```bash
# Remove logs older than 7 days (default)
php bin/metamorphose log:clear

# Remove logs older than 30 days
php bin/metamorphose log:clear --days=30

# Remove all logs (with confirmation)
php bin/metamorphose log:clear --all

# Remove all logs without confirmation
php bin/metamorphose log:clear --all -y

# Remove old logs without confirmation
php bin/metamorphose log:clear --days=14 -y
```

**What it does:**
1. Scans the log directory configured in `config/log.php`
2. Lists found log files
3. Removes logs according to specified options:
   - With `--days=N`: Removes only logs older than N days
   - With `--all`: Removes all log files
4. Shows statistics of removed files (count and size)

**Example output:**
```
Found 5 log file(s) older than 7 day(s) (2.5 MB)
  - 2025-12-10.log (2025-12-10, 512.5 KB)
  - 2025-12-11.log (2025-12-11, 1.2 MB)
  - 2025-12-12.log (2025-12-12, 786.3 KB)

Are you sure you want to delete these files? (yes/no): yes

✅ Successfully deleted 3 file(s) (2.5 MB)
```

**Note:** The log directory is configured in `config/log.php` through the `path` option.

### migrate

Runs database migrations for a specific scope.

**Usage:**
```bash
php bin/metamorphose migrate --scope=core
php bin/metamorphose migrate --scope=tenant
php bin/metamorphose migrate --scope=unit
```

**Shortcut:**
```bash
php bin/migrate --scope=core
```

**Options:**
- `--scope=core`: Run core scope migrations
- `--scope=tenant`: Run tenant scope migrations
- `--scope=unit`: Run unit scope migrations

**How it works:**
1. Scans all enabled modules for migrations in the specified scope
2. Checks which migrations have already been executed
3. Runs pending migrations in order
4. Records executed migrations in the `migrations` table

**Example output:**
```
Migrações executadas com sucesso para o escopo: core
```

**Error handling:**
- If a migration fails, it rolls back the transaction
- Previous migrations remain executed
- Check the error message for details

## Creating Custom Commands

You can create custom CLI commands by implementing `CommandInterface`:

### Step 1: Create Command Class

Create `app/CLI/Commands/YourCommand.php`:

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class YourCommand implements CommandInterface
{
    public function name(): string
    {
        return 'your:command';
    }

    public function description(): string
    {
        return 'Description of your command';
    }

    public function handle(array $args): int
    {
        // Your command logic here
        echo "Command executed!\n";
        return 0; // 0 = success, 1+ = error
    }
}
```

### Step 2: Register Command

Edit `app/CLI/KernelCLI.php`:

```php
use Metamorphose\CLI\Commands\YourCommand;

private function registerDefaultCommands(): void
{
    $this->register(new ModuleMakeCommand());
    $this->register(new MigrateCommand());
    $this->register(new YourCommand()); // Add your command
}
```

### Step 3: Use Your Command

```bash
php bin/metamorphose your:command
```

## Command Interface

All commands must implement `CommandInterface`:

```php
interface CommandInterface
{
    /**
     * Command name (e.g., 'module:make')
     */
    public function name(): string;
    
    /**
     * Command description
     */
    public function description(): string;
    
    /**
     * Execute the command
     * 
     * @param array $args Command arguments
     * @return int Exit code (0 = success, 1+ = error)
     */
    public function handle(array $args): int;
}
```

## Command Arguments

Commands receive arguments as an array:

```php
public function handle(array $args): int
{
    // $args[0] = first argument
    // $args[1] = second argument
    // etc.
    
    if (empty($args[0])) {
        echo "Error: Argument required\n";
        return 1;
    }
    
    $value = $args[0];
    // Process...
    
    return 0;
}
```

## Parsing Options

To parse options like `--scope=core`:

```php
private function parseScope(array $args): ?string
{
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--scope=')) {
            return substr($arg, 8);
        }
    }
    
    return null;
}
```

## Exit Codes

- `0`: Success
- `1` or higher: Error

Use appropriate exit codes to indicate success or failure:

```php
public function handle(array $args): int
{
    try {
        // Do something
        return 0; // Success
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return 1; // Error
    }
}
```

## Help Command

To show available commands:

```bash
php bin/metamorphose
```

This displays:
```
Metamorphose Framework CLI
==========================

Comandos disponíveis:

  module:make          Cria um novo módulo
  migrate              Executa migrações de banco de dados (--scope=core|tenant|unit)
```

## Best Practices

1. **Validate arguments**: Check required arguments before processing
2. **Provide clear errors**: Use descriptive error messages
3. **Return proper exit codes**: 0 for success, 1+ for errors
4. **Handle exceptions**: Catch and display errors gracefully
5. **Document your command**: Add comments explaining what the command does

## Examples

### Example: Cache Clear Command

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class CacheClearCommand implements CommandInterface
{
    public function name(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Clear application cache';
    }

    public function handle(array $args): int
    {
        $cachePath = __DIR__ . '/../../storage/cache';
        
        if (!is_dir($cachePath)) {
            echo "Cache directory not found\n";
            return 1;
        }
        
        $files = glob($cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        echo "Cache cleared successfully\n";
        return 0;
    }
}
```

### Example: List Modules Command

```php
<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

class ModuleListCommand implements CommandInterface
{
    public function name(): string
    {
        return 'module:list';
    }

    public function description(): string
    {
        return 'List all enabled modules';
    }

    public function handle(array $args): int
    {
        $config = require __DIR__ . '/../../../config/modules.php';
        $modules = $config['enabled'] ?? [];
        
        echo "Enabled modules:\n\n";
        foreach ($modules as $module) {
            echo "  - {$module}\n";
        }
        
        return 0;
    }
}
```

