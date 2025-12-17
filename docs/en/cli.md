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

