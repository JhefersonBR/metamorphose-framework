<?php

namespace Metamorphose\Kernel\Migration;

use Doctrine\DBAL\Connection;
use Metamorphose\Kernel\Database\DBALConnectionResolver;

/**
 * Executor de migrações usando Doctrine DBAL
 * 
 * Executa migrações de banco de dados para os escopos:
 * - core (global)
 * - tenant
 * - unit
 */
class MigrationRunner
{
    private DBALConnectionResolver $connectionResolver;
    private array $migrationPaths;

    public function __construct(
        DBALConnectionResolver $connectionResolver,
        array $migrationPaths
    ) {
        $this->connectionResolver = $connectionResolver;
        $this->migrationPaths = $migrationPaths;
    }

    public function run(string $scope): void
    {
        if (!isset($this->migrationPaths[$scope])) {
            throw new \InvalidArgumentException("Escopo de migração inválido: {$scope}");
        }

        $paths = $this->migrationPaths[$scope];
        
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        
        if (empty($paths)) {
            return;
        }

        $connection = $this->getConnectionForScope($scope);
        $this->ensureMigrationsTable($connection);
        
        $executed = $this->getExecutedMigrations($connection);
        
        $allMigrations = [];
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $migrations = $this->getMigrations($path);
            $allMigrations = array_merge($allMigrations, $migrations);
        }
        
        usort($allMigrations, fn($a, $b) => strcmp($a['name'], $b['name']));
        
        foreach ($allMigrations as $migration) {
            if (in_array($migration['name'], $executed)) {
                continue;
            }
            
            $this->executeMigration($connection, $migration);
        }
    }

    private function getConnectionForScope(string $scope): Connection
    {
        // Para migrations, permitimos conexão padrão sem tenant/unit ID
        return match ($scope) {
            'core' => $this->connectionResolver->resolveCore(),
            'tenant' => $this->connectionResolver->resolveTenant(null, true),
            'unit' => $this->connectionResolver->resolveUnit(null, true),
            default => throw new \InvalidArgumentException("Escopo inválido: {$scope}"),
        };
    }

    private function ensureMigrationsTable(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();
        
        // Verificar se a tabela já existe
        if ($schemaManager->tablesExist(['migrations'])) {
            return;
        }
        
        // Detectar se é SQLite pela plataforma
        $platform = $connection->getDatabasePlatform();
        $platformClass = get_class($platform);
        $isSqlite = strpos($platformClass, 'SQLite') !== false;
        
        if ($isSqlite) {
            $sql = "CREATE TABLE migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        $connection->executeStatement($sql);
    }

    private function getMigrations(string $path): array
    {
        $files = glob($path . '/*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $migrations[] = [
                'name' => $name,
                'path' => $file,
            ];
        }
        
        usort($migrations, fn($a, $b) => strcmp($a['name'], $b['name']));
        
        return $migrations;
    }

    private function getExecutedMigrations(Connection $connection): array
    {
        $results = $connection->fetchFirstColumn("SELECT migration FROM migrations ORDER BY migration");
        return $results;
    }

    private function executeMigration(Connection $connection, array $migration): void
    {
        $connection->beginTransaction();
        
        try {
            require_once $migration['path'];
            
            $className = $this->getMigrationClassName($migration['name']);
            
            if (!class_exists($className)) {
                throw new \RuntimeException("Classe de migração não encontrada: {$className}");
            }
            
            $instance = new $className($connection);
            
            if (!method_exists($instance, 'up')) {
                throw new \RuntimeException("Método 'up' não encontrado em {$className}");
            }
            
            $instance->up();
            
            $connection->insert('migrations', ['migration' => $migration['name']]);
            
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \RuntimeException(
                "Erro ao executar migração {$migration['name']}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function getMigrationClassName(string $migrationName): string
    {
        // Converte snake_case para PascalCase mantendo números
        // Exemplo: 0001_create_users_table -> Migration0001CreateUsersTable
        $parts = explode('_', $migrationName);
        $parts = array_map(function($part) {
            // Se for numérico, mantém como está, senão capitaliza
            return is_numeric($part) ? $part : ucfirst($part);
        }, $parts);
        $className = implode('', $parts);
        
        // Adiciona prefixo Migration se não começar com ele
        if (!str_starts_with($className, 'Migration')) {
            $className = 'Migration' . $className;
        }
        
        return $className;
    }
}

