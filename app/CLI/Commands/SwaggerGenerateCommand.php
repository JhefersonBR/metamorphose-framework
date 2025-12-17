<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;
use Metamorphose\Kernel\Swagger\SwaggerGenerator;

/**
 * Comando para gerar/atualizar documentação Swagger
 * 
 * Escaneia controllers e gera documentação OpenAPI.
 */
class SwaggerGenerateCommand implements CommandInterface
{
    public function name(): string
    {
        return 'swagger:generate';
    }

    public function description(): string
    {
        return 'Generates/updates Swagger documentation based on APIs';
    }

    public function handle(array $args): int
    {
        $outputPath = __DIR__ . '/../../../public/swagger.json';
        $scanPath = __DIR__ . '/../../Modules';

        echo "Generating Swagger documentation...\n";
        echo "Scanning: {$scanPath}\n";
        echo "Output: {$outputPath}\n\n";

        try {
            $generator = new SwaggerGenerator($scanPath, $outputPath);
            
            // Add additional paths if necessary
            $generator->addScanPath(__DIR__ . '/../../Kernel');
            
            if ($generator->generate()) {
                echo "✅ Swagger documentation generated successfully!\n";
                echo "File: {$outputPath}\n";
                echo "\nAccess: http://localhost/swagger-ui to view\n";
                return 0;
            } else {
                echo "❌ Error generating Swagger documentation\n";
                return 1;
            }
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

