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
        return 'Completely removes a module';
    }

    public function handle(array $args): int
    {
        if (empty($args[0])) {
            echo "Error: Module name is required\n";
            echo "Usage: module:remove {Name} [-y]\n";
            return 1;
        }

        $moduleName = $args[0];
        $autoConfirm = in_array('-y', $args) || in_array('--yes', $args);
        $modulePath = __DIR__ . '/../../Modules/' . $moduleName;
        
        if (!is_dir($modulePath)) {
            echo "Error: Module '{$moduleName}' does not exist\n";
            return 1;
        }

        $className = $this->toClassName($moduleName);
        $moduleClass = "\\Metamorphose\\Modules\\{$className}\\Module";

        // Check if registered in config/modules.php
        $isRegistered = $this->isRegisteredInConfig($moduleClass);
        
        // Show module information
        echo "\n⚠️  WARNING: You are about to remove module '{$moduleName}'\n\n";
        echo "Information:\n";
        echo "  - Path: {$modulePath}\n";
        if ($isRegistered) {
            echo "  - Status: Registered in config/modules.php\n";
        } else {
            echo "  - Status: Not registered in config/modules.php\n";
        }
        echo "\nThis action will:\n";
        echo "  - Remove module from config/modules.php (if registered)\n";
        echo "  - Permanently delete the module folder\n";
        echo "  - Update Composer autoloader\n";
        echo "\n";

        // Request confirmation (skip if -y flag is present)
        if (!$autoConfirm && !$this->confirm("Are you sure you want to remove module '{$moduleName}'? (yes/no): ")) {
            echo "Operation cancelled.\n";
            return 0;
        }

        // 1. Remove from config/modules.php if registered
        $removedFromConfig = $this->removeFromConfig($moduleClass);
        
        // 2. Delete module folder
        $this->removeDirectory($modulePath);
        
        // 3. Execute composer dump-autoload
        $this->dumpAutoload();

        echo "\n✅ Module '{$moduleName}' removed successfully!\n";
        if ($removedFromConfig) {
            echo "  - Removed from config/modules.php\n";
        }
        echo "  - Folder deleted: {$modulePath}\n";
        echo "  - Autoloader updated\n";
        
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
        
        // When PHP loads the config file, ::class is evaluated and returns the full class name
        // So we need to compare the actual class name, not the string with ::class
        foreach ($enabled as $enabledModule) {
            if (is_string($enabledModule)) {
                // Normalize both strings for comparison (remove leading backslash and ::class)
                $normalizedEnabled = ltrim(str_replace('::class', '', $enabledModule), '\\');
                $normalizedModule = ltrim($moduleClass, '\\');
                
                if ($normalizedEnabled === $normalizedModule) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function confirm(string $prompt): bool
    {
        echo $prompt;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        $answer = strtolower($line);
        return in_array($answer, ['yes', 'y', '1']);
    }

    private function removeFromConfig(string $moduleClass): bool
    {
        $configPath = __DIR__ . '/../../../config/modules.php';
        
        if (!file_exists($configPath)) {
            return false;
        }

        // Load config to check if module exists
        $config = require $configPath;
        $enabled = $config['enabled'] ?? [];
        
        // Find the module in the list
        // When PHP loads the config file, ::class is evaluated and returns the full class name
        // Example: \Metamorphose\Modules\Example\Module::class becomes "Metamorphose\Modules\Example\Module"
        $key = false;
        $normalizedModule = ltrim($moduleClass, '\\');
        
        foreach ($enabled as $index => $enabledModule) {
            if (is_string($enabledModule)) {
                // Normalize: remove leading backslash (PHP returns class name without it)
                $normalizedEnabled = ltrim($enabledModule, '\\');
                
                if ($normalizedEnabled === $normalizedModule) {
                    $key = $index;
                    break;
                }
            }
        }
        
        if ($key === false) {
            // Module not found in config
            return false;
        }

        // Remove from list
        unset($enabled[$key]);
        $enabled = array_values($enabled); // Reindex array

        // Rewrite configuration file
        $content = "<?php\n\nreturn [\n    'enabled' => [\n";
        
        foreach ($enabled as $module) {
            if (is_string($module)) {
                // Always write with ::class format
                $content .= "        \\{$module}::class,\n";
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

        $result = file_put_contents($configPath, $content);
        return $result !== false;
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
            echo "Warning: Could not execute 'composer dump-autoload'\n";
            echo "Run manually: composer dump-autoload\n";
        }
    }

    private function toClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
