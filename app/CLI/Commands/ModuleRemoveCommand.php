<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

/**
 * Comando para remover um módulo
 * 
 * Remove um módulo completamente:
 * - Remove de config/modules.php (se estiver registrado)
 * - Exclui a pasta do módulo
 * - Executa composer dump-autoload
 */
class ModuleRemoveCommand implements CommandInterface
{
    public function name(): string
    {
        return 'module:remove';
    }

    public function description(): string
    {
        return 'Remove um módulo completamente';
    }

    public function handle(array $args): int
    {
        if (empty($args[0])) {
            echo "Erro: Nome do módulo é obrigatório\n";
            echo "Uso: module:remove {Nome}\n";
            return 1;
        }

        $moduleName = $args[0];
        $modulePath = __DIR__ . '/../../Modules/' . $moduleName;
        
        if (!is_dir($modulePath)) {
            echo "Erro: Módulo '{$moduleName}' não existe\n";
            return 1;
        }

        $className = $this->toClassName($moduleName);
        $moduleClass = "\\Metamorphose\\Modules\\{$className}\\Module";

        // Verificar se está registrado em config/modules.php
        $isRegistered = $this->isRegisteredInConfig($moduleClass);
        
        // Mostrar informações do módulo
        echo "\n⚠️  ATENÇÃO: Você está prestes a remover o módulo '{$moduleName}'\n\n";
        echo "Informações:\n";
        echo "  - Caminho: {$modulePath}\n";
        if ($isRegistered) {
            echo "  - Status: Registrado em config/modules.php\n";
        } else {
            echo "  - Status: Não registrado em config/modules.php\n";
        }
        echo "\nEsta ação irá:\n";
        echo "  - Remover o módulo de config/modules.php (se registrado)\n";
        echo "  - Excluir permanentemente a pasta do módulo\n";
        echo "  - Atualizar o autoloader do Composer\n";
        echo "\n";

        // Solicitar confirmação
        if (!$this->confirm("Tem certeza que deseja remover o módulo '{$moduleName}'? (sim/não): ")) {
            echo "Operação cancelada.\n";
            return 0;
        }

        // 1. Remover de config/modules.php se estiver registrado
        $removedFromConfig = $this->removeFromConfig($moduleClass);
        
        // 2. Excluir pasta do módulo
        $this->removeDirectory($modulePath);
        
        // 3. Executar composer dump-autoload
        $this->dumpAutoload();

        echo "\n✅ Módulo '{$moduleName}' removido com sucesso!\n";
        if ($removedFromConfig) {
            echo "  - Removido de config/modules.php\n";
        }
        echo "  - Pasta excluída: {$modulePath}\n";
        echo "  - Autoloader atualizado\n";
        
        return 0;
    }

    private function isRegisteredInConfig(string $moduleClass): bool
    {
        $configPath = __DIR__ . '/../../../config/modules.php';
        
        if (!file_exists($configPath)) {
            return false;
        }

        $config = require $configPath;
        $enabled = $config['enabled'] ?? [];
        
        return in_array($moduleClass, $enabled);
    }

    private function confirm(string $prompt): bool
    {
        echo $prompt;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        $answer = strtolower($line);
        return in_array($answer, ['sim', 's', 'yes', 'y', '1']);
    }

    private function removeFromConfig(string $moduleClass): bool
    {
        $configPath = __DIR__ . '/../../../config/modules.php';
        
        if (!file_exists($configPath)) {
            return false;
        }

        $config = require $configPath;
        $enabled = $config['enabled'] ?? [];
        
        // Verificar se o módulo está na lista
        $key = array_search($moduleClass, $enabled);
        if ($key === false) {
            return false;
        }

        // Remover da lista
        unset($enabled[$key]);
        $enabled = array_values($enabled); // Reindexar array

        // Reescrever arquivo de configuração
        $content = "<?php\n\nreturn [\n    'enabled' => [\n";
        
        foreach ($enabled as $module) {
            if (is_string($module)) {
                $content .= "        {$module}::class,\n";
            } elseif (is_array($module)) {
                $content .= "        [\n";
                foreach ($module as $key => $value) {
                    if (is_string($value)) {
                        $content .= "            '{$key}' => '{$value}',\n";
                    } elseif (is_bool($value)) {
                        $content .= "            '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
                    } elseif (is_null($value)) {
                        $content .= "            '{$key}' => null,\n";
                    }
                }
                $content .= "        ],\n";
            }
        }
        
        $content .= "    ],\n];\n";

        file_put_contents($configPath, $content);
        return true;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    private function dumpAutoload(): void
    {
        $composerPath = __DIR__ . '/../../../composer.json';
        
        if (!file_exists($composerPath)) {
            return;
        }

        // Executar composer dump-autoload
        $command = 'composer dump-autoload';
        $output = [];
        $returnVar = 0;
        
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            echo "Aviso: Não foi possível executar 'composer dump-autoload'\n";
            echo "Execute manualmente: composer dump-autoload\n";
        }
    }

    private function toClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
