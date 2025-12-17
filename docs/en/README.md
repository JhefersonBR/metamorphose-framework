# Metamorphose Framework - Documentation

Metamorphose Framework is a modular, multi-tenant PHP application kernel based on Slim Framework.

## Table of Contents

1. [Installation](installation.md)
2. [Getting Started](getting-started.md)
3. [Architecture](architecture.md)
4. [Modules](modules.md)
5. [CLI Commands](cli.md)
6. [Contexts](contexts.md)
7. [Database](database.md)
8. [Logging](logging.md)
9. [Permissions](permissions.md)
10. [Microservices](microservices.md)

## Overview

Metamorphose Framework is designed to be:

- **Modular**: Plug-and-play modules that can be easily added or removed
- **Multi-tenant**: Supports core, tenant, and unit scopes
- **Context-aware**: Explicit contexts for tenant, unit, and request
- **Runtime-agnostic**: Works with PHP-FPM, Swoole, and FrankenPHP
- **PSR-compliant**: Follows PSR-4, PSR-7, PSR-11, and PSR-15 standards
- **Explicit**: No hidden magic, all code is explicit and readable
- **Microservices-ready**: Easily extract modules to separate microservices

## Key Features

- Modular architecture with pluggable modules
- Multi-tenant support (core, tenant, unit)
- Explicit context management
- Flexible database connections per scope
- Structured logging with HTTP middleware
- Multi-scope permission system
- Built-in CLI for module creation and migrations
- No global mutable state
- Compatible with persistent runtimes
- **Microservices support**: Run modules as separate services

## Requirements

- PHP >= 8.1
- Composer
- MySQL/MariaDB (or compatible database)

## Quick Start

```bash
# Install dependencies
composer install

# Create a new module
php bin/metamorphose module:make MyModule

# Run migrations
php bin/metamorphose migrate --scope=core
```

## Documentation Structure

Each section of the documentation covers a specific aspect of the framework:

- **Installation**: How to set up the framework
- **Getting Started**: Your first steps with the framework
- **Architecture**: Understanding the framework's architecture
- **Modules**: Creating and managing modules
- **CLI Commands**: Using the command-line interface
- **Contexts**: Working with tenant, unit, and request contexts
- **Database**: Database connections and migrations
- **Logging**: Configuring and using the logging system
- **Permissions**: Implementing permission checks
- **Microservices**: Extracting modules to separate microservices

