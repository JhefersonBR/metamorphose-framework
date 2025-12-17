# Architecture

This document explains the architecture of Metamorphose Framework, its design principles, and how components interact.

## Design Principles

### 1. Modularity

The framework is built around modules that can be plugged in or removed without affecting other modules. Each module is self-contained and follows the `ModuleInterface`.

### 2. Explicit Contexts

Instead of relying on global state, the framework uses explicit context objects:
- `TenantContext`: Current tenant information
- `UnitContext`: Current unit information
- `RequestContext`: Current request information

### 3. No Hidden Magic

All code is explicit and readable. There are no hidden behaviors or magic methods that make code hard to understand.

### 4. Runtime Agnostic

The framework works with:
- PHP-FPM (traditional request/response cycle)
- Swoole (persistent runtime)
- FrankenPHP (persistent runtime)

No code relies on global mutable state that would break in persistent runtimes.

### 5. PSR Compliance

The framework follows PSR standards:
- PSR-4: Autoloading
- PSR-7: HTTP messages
- PSR-11: Container interface
- PSR-15: HTTP server middleware

## Architecture Layers

```
┌─────────────────────────────────────────┐
│         HTTP Request (PSR-7)            │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Middleware Stack                │
│  - ContextMiddleware                    │
│  - HttpLogMiddleware                    │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Route Handler                   │
│  (Module Controllers)                   │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Services / Repositories         │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Database (ConnectionResolver)   │
└─────────────────────────────────────────┘
```

## Core Components

### Bootstrap

Located in `app/Bootstrap/`, these files initialize the application:

- `container.php`: Creates and configures the PSR-11 container
- `app.php`: Creates the Slim application instance
- `middleware.php`: Registers global middlewares
- `routes.php`: Loads routes from modules

### Kernel

The kernel (`app/Kernel/`) contains core framework functionality:

#### Context (`app/Kernel/Context/`)

- `TenantContext`: Manages tenant information
- `UnitContext`: Manages unit information
- `RequestContext`: Manages request information and generates unique request IDs

#### Module (`app/Kernel/Module/`)

- `ModuleInterface`: Contract that all modules must implement
- `ModuleLoader`: Loads and initializes modules

#### Database (`app/Kernel/Database/`)

- `ConnectionResolverInterface`: Interface for resolving database connections
- `ConnectionResolver`: Resolves connections based on scope (core, tenant, unit)

#### Log (`app/Kernel/Log/`)

- `LoggerInterface`: PSR-3 compatible logger interface
- `LoggerFactory`: Creates logger instances
- `LogContext`: Enriches log entries with context information
- `HttpLogMiddleware`: Logs HTTP requests automatically
- `NullLogger`: Discards logs when logging is disabled

#### Permission (`app/Kernel/Permission/`)

- `PermissionService`: Validates permissions
- `PermissionResolver`: Resolves permission scope

#### Migration (`app/Kernel/Migration/`)

- `MigrationRunner`: Executes database migrations

### Modules

Modules (`app/Modules/`) are self-contained units of functionality. Each module:

1. Implements `ModuleInterface`
2. Registers services in `register()`
3. Performs initialization in `boot()`
4. Defines routes in `routes()`

### CLI

The CLI (`app/CLI/`) provides command-line tools:

- `KernelCLI`: Main CLI entry point
- `CommandInterface`: Contract for commands
- Commands: `ModuleMakeCommand`, `MigrateCommand`

## Request Flow

1. **HTTP Request arrives** at `public/index.php`
2. **Container is built** via `Bootstrap\buildContainer()`
3. **Slim App is created** via `Bootstrap\createApp()`
4. **Middlewares are registered** via `Bootstrap\registerMiddlewares()`
   - `ContextMiddleware` populates contexts
   - `HttpLogMiddleware` logs requests (if enabled)
5. **Routes are loaded** via `Bootstrap\loadRoutes()`
   - `ModuleLoader` loads all enabled modules
   - Each module registers its routes
6. **Request is handled** by the matching route handler
7. **Response is returned** and logged

## Dependency Injection

The framework uses PHP-DI for dependency injection. Services are registered in:

1. `app/Bootstrap/container.php` - Core framework services
2. Module `register()` methods - Module-specific services

Services are resolved automatically via constructor injection when type-hinted.

## Multi-Tenancy

The framework supports three scopes:

### Core (Global)

- Shared across all tenants
- Used for system-wide data
- Resolved via `ConnectionResolver::resolveCore()`

### Tenant

- Isolated per tenant
- Used for tenant-specific data
- Resolved via `ConnectionResolver::resolveTenant()`
- Requires `TenantContext` to be populated

### Unit

- Isolated per unit (sub-tenant)
- Used for unit-specific data
- Resolved via `ConnectionResolver::resolveUnit()`
- Requires `UnitContext` to be populated

## Module Isolation

Modules are isolated from each other:

- No direct dependencies between modules
- Communication via shared services or events (future)
- Each module manages its own:
  - Controllers
  - Services
  - Repositories
  - Entities
  - Migrations

## Extension Points

The framework can be extended at several points:

1. **Modules**: Add new functionality via modules
2. **Middlewares**: Add custom middleware
3. **Services**: Register custom services in the container
4. **Commands**: Add CLI commands
5. **Log Handlers**: Customize logging behavior

## Best Practices

1. **Keep modules independent**: Don't create dependencies between modules
2. **Use contexts explicitly**: Always inject context objects, never access globals
3. **Register services properly**: Use the container for dependency injection
4. **Follow PSR standards**: Ensure compatibility with the ecosystem
5. **Write explicit code**: Avoid magic methods and hidden behaviors

## Future Considerations

The architecture is designed to support:

- Microservices extraction: Modules can be extracted to separate services
- Event system: For module communication
- Queue system: For background jobs
- Caching layer: For performance optimization

