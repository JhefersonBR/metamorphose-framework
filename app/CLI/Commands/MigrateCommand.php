<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;
use Metamorphose\Kernel\Database\ConnectionResolver;
use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Migration\MigrationRunner;

/**
 * Comando para executar migrações
 * 
 * Executa migrações para um escopo específico:
 * - core
 * - tenant
 * - unit
 */
class MigrateCommand implements CommandInterface
{
    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Executa migrações de banco de dados (--scope=core|tenant|unit)';
    }

    public function handle(array $args): int
    {
        $scope = $this->parseScope($args);
        
        if ($scope === null) {
            echo "Erro: Escopo é obrigatório\n";
            echo "Uso: migrate --scope=core|tenant|unit\n";
            return 1;
        }

        if (!in_array($scope, ['core', 'tenant', 'unit'])) {
            echo "Erro: Escopo inválido. Use: core, tenant ou unit\n";
            return 1;
        }

        try {
            $this->runMigrations($scope);
            echo "Migrações executadas com sucesso para o escopo: {$scope}\n";
            return 0;
        } catch (\Exception $e) {
            echo "Erro ao executar migrações: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    private function parseScope(array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--scope=')) {
                return substr($arg, 8);
            }
        }
        
        return null;
    }

    private function runMigrations(string $scope): void
    {
        $config = require __DIR__ . '/../../../config/database.php';
        $tenantContext = new TenantContext();
        $unitContext = new UnitContext();
        
        $connectionResolver = new ConnectionResolver($config, $tenantContext, $unitContext);
        
        $migrationPaths = $this->getMigrationPaths($scope);
        
        $runner = new MigrationRunner($connectionResolver, $migrationPaths);
        $runner->run($scope);
    }

    private function getMigrationPaths(string $scope): array
    {
        $basePath = __DIR__ . '/../../Modules';
        $paths = [];
        
        $modules = glob($basePath . '/*', GLOB_ONLYDIR);
        
        foreach ($modules as $modulePath) {
            $migrationPath = $modulePath . '/Migrations/' . $scope;
            if (is_dir($migrationPath)) {
                $paths[] = $migrationPath;
            }
        }
        
        return [$scope => $paths];
    }
}

