<?php

namespace Metamorphose\Kernel\Migration;

use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use PDO;

/**
 * Executor de migrações
 * 
 * Executa migrações de banco de dados para os escopos:
 * - core (global)
 * - tenant
 * - unit
 */
class MigrationRunner
{
    private ConnectionResolverInterface $connectionResolver;
    private array $migrationPaths;

    public function __construct(
        ConnectionResolverInterface $connectionResolver,
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

    private function getConnectionForScope(string $scope): PDO
    {
        return match ($scope) {
            'core' => $this->connectionResolver->resolveCore(),
            'tenant' => $this->connectionResolver->resolveTenant(),
            'unit' => $this->connectionResolver->resolveUnit(),
            default => throw new \InvalidArgumentException("Escopo inválido: {$scope}"),
        };
    }

    private function ensureMigrationsTable(PDO $connection): void
    {
        // Detectar se é SQLite pelo driver
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        $connection->exec($sql);
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

    private function getExecutedMigrations(PDO $connection): array
    {
        $stmt = $connection->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function executeMigration(PDO $connection, array $migration): void
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
            
            $stmt = $connection->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration['name']]);
            
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

