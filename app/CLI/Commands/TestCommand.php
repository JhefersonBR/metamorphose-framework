<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

/**
 * Command to run unit tests
 * 
 * Executes PHPUnit tests with various options.
 */
class TestCommand implements CommandInterface
{
    public function name(): string
    {
        return 'test';
    }

    public function description(): string
    {
        return 'Runs unit tests using PHPUnit';
    }

    public function handle(array $args): int
    {
        $filter = $this->parseOption($args, '--filter', null);
        $coverage = in_array('--coverage', $args);
        $verbose = in_array('-v', $args) || in_array('--verbose', $args);
        $stopOnFailure = in_array('--stop-on-failure', $args);
        
        $phpunitPath = __DIR__ . '/../../../vendor/bin/phpunit';
        
        if (!file_exists($phpunitPath)) {
            echo "❌ Error: PHPUnit not found. Run 'composer install' first.\n";
            return 1;
        }

        $command = escapeshellarg($phpunitPath);
        
        // Add filter if specified
        if ($filter !== null) {
            $command .= ' --filter ' . escapeshellarg($filter);
        }
        
        // Add coverage if requested
        if ($coverage) {
            $command .= ' --coverage-text';
        }
        
        // Add verbose if requested
        if ($verbose) {
            $command .= ' --verbose';
        }
        
        // Add stop on failure if requested
        if ($stopOnFailure) {
            $command .= ' --stop-on-failure';
        }

        echo "Running tests...\n\n";
        
        passthru($command, $returnVar);
        
        return $returnVar;
    }

    private function parseOption(array $args, string $option, ?string $default): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, $option . '=')) {
                return substr($arg, strlen($option) + 1);
            }
        }
        
        return $default;
    }
}

