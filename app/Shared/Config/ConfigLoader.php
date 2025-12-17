<?php

namespace Metamorphose\Shared\Config;

/**
 * Carregador de configurações
 */
class ConfigLoader
{
    public static function load(string $file): array
    {
        $path = __DIR__ . '/../../../config/' . $file . '.php';
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Arquivo de configuração não encontrado: {$path}");
        }
        
        return require $path;
    }
}

