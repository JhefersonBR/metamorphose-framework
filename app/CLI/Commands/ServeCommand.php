<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;
use Metamorphose\Kernel\Swagger\SwaggerGenerator;

/**
 * Comando para iniciar servidor de desenvolvimento
 * 
 * Inicia servidor PHP built-in com aplicação e Swagger.
 */
class ServeCommand implements CommandInterface
{
    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Starts local PHP server with application and Swagger';
    }

    public function handle(array $args): int
    {
        $host = $this->parseOption($args, '--host', 'localhost');
        $port = (int) $this->parseOption($args, '--port', '8000');
        $generateSwagger = !in_array('--no-swagger', $args);

        $publicPath = __DIR__ . '/../../../public';
        
        if (!is_dir($publicPath)) {
            echo "❌ Error: public directory not found\n";
            return 1;
        }

        // Generate Swagger documentation if necessary
        if ($generateSwagger) {
            echo "📝 Generating Swagger documentation...\n";
            $this->generateSwagger();
        }

        $address = "{$host}:{$port}";
        $url = "http://{$address}";
        
        echo "\n";
        echo "🚀 Metamorphose Framework - Development Server\n";
        echo "===============================================\n\n";
        echo "📍 Server running at: {$url}\n";
        echo "📚 Swagger UI: {$url}/swagger-ui\n";
        echo "📄 Swagger JSON: {$url}/swagger.json\n";
        echo "\n";
        echo "⚠️  Press Ctrl+C to stop the server\n";
        echo "\n";

        // Iniciar servidor PHP
        $command = sprintf(
            'php -S %s -t %s',
            escapeshellarg($address),
            escapeshellarg($publicPath)
        );

        passthru($command, $returnVar);
        
        return $returnVar;
    }

    private function parseOption(array $args, string $option, string $default): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, $option . '=')) {
                return substr($arg, strlen($option) + 1);
            }
        }
        
        return $default;
    }

    private function generateSwagger(): void
    {
        $outputPath = __DIR__ . '/../../../public/swagger.json';
        $scanPath = __DIR__ . '/../../Modules';

        try {
            $generator = new SwaggerGenerator($scanPath, $outputPath);
            $generator->addScanPath(__DIR__ . '/../../Kernel');
            
            if ($generator->generate()) {
                echo "✅ Swagger documentation generated\n";
            } else {
                echo "⚠️  Warning: Could not generate Swagger documentation\n";
            }
        } catch (\Exception $e) {
            echo "⚠️  Warning: Error generating Swagger: " . $e->getMessage() . "\n";
            echo "   You can generate manually with: php bin/metamorphose swagger:generate\n";
        }
    }
}

