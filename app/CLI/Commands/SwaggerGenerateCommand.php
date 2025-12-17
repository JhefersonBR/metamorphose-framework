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
        return 'Gera/atualiza a documentação Swagger baseada nas APIs';
    }

    public function handle(array $args): int
    {
        $outputPath = __DIR__ . '/../../../public/swagger.json';
        $scanPath = __DIR__ . '/../../Modules';

        echo "Gerando documentação Swagger...\n";
        echo "Escaneando: {$scanPath}\n";
        echo "Saída: {$outputPath}\n\n";

        try {
            $generator = new SwaggerGenerator($scanPath, $outputPath);
            
            // Adicionar caminhos adicionais se necessário
            $generator->addScanPath(__DIR__ . '/../../Kernel');
            
            if ($generator->generate()) {
                echo "✅ Documentação Swagger gerada com sucesso!\n";
                echo "Arquivo: {$outputPath}\n";
                echo "\nAcesse: http://localhost/swagger-ui para visualizar\n";
                return 0;
            } else {
                echo "❌ Erro ao gerar documentação Swagger\n";
                return 1;
            }
        } catch (\Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

