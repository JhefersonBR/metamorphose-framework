<?php

namespace Metamorphose\CLI;

use Metamorphose\CLI\Commands\MigrateCommand;
use Metamorphose\CLI\Commands\ModuleMakeCommand;
use Metamorphose\CLI\Commands\ModuleRemoveCommand;
use Metamorphose\CLI\Commands\ServeCommand;
use Metamorphose\CLI\Commands\SwaggerGenerateCommand;
use Metamorphose\CLI\Commands\TestCommand;

/**
 * Kernel CLI
 * 
 * Registra e executa comandos CLI do framework.
 */
class KernelCLI
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void
    {
        $this->register(new ModuleMakeCommand());
        $this->register(new ModuleRemoveCommand());
        $this->register(new MigrateCommand());
        $this->register(new SwaggerGenerateCommand());
        $this->register(new ServeCommand());
        $this->register(new TestCommand());
    }

    public function register(CommandInterface $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    public function run(array $argv): int
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return 1;
        }

        $commandName = $argv[1];
        $args = array_slice($argv, 2);

        if (!isset($this->commands[$commandName])) {
            echo "Command not found: {$commandName}\n";
            $this->showHelp();
            return 1;
        }

        $command = $this->commands[$commandName];
        return $command->handle($args);
    }

    private function showHelp(): void
    {
        echo "Metamorphose Framework CLI\n";
        echo "==========================\n\n";
        echo "Available commands:\n\n";
        
        foreach ($this->commands as $command) {
            echo sprintf("  %-20s %s\n", $command->name(), $command->description());
        }
        
        echo "\n";
    }
}

