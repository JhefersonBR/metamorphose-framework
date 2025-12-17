<?php

namespace Metamorphose\Kernel\Swagger;

use OpenApi\Generator;

/**
 * Gerador de documentação Swagger/OpenAPI
 * 
 * Gera documentação OpenAPI a partir de anotações nos controllers.
 */
class SwaggerGenerator
{
    private string $scanPath;
    private string $outputPath;
    private array $scanPaths;

    public function __construct(string $scanPath, string $outputPath)
    {
        $this->scanPath = $scanPath;
        $this->outputPath = $outputPath;
        $this->scanPaths = [];
    }

    public function addScanPath(string $path): void
    {
        $this->scanPaths[] = $path;
    }

    public function generate(): bool
    {
        $paths = array_merge([$this->scanPath], $this->scanPaths);
        
        try {
            $openapi = Generator::scan($paths, [
                'exclude' => [
                    'vendor',
                    'tests',
                    'node_modules',
                ],
            ]);

            if ($openapi === null) {
                return false;
            }

            // Criar diretório se não existir
            $outputDir = dirname($this->outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Salvar arquivo JSON
            file_put_contents($this->outputPath, $openapi->toJson());

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Erro ao gerar documentação Swagger: " . $e->getMessage(), 0, $e);
        }
    }

    public function getOutputPath(): string
    {
        return $this->outputPath;
    }
}

