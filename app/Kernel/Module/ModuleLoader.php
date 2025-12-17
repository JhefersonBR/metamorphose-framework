<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Carregador de módulos
 * 
 * Carrega e inicializa módulos definidos em config/modules.php
 */
class ModuleLoader
{
    private ContainerInterface $container;
    private App $app;
    private array $modules;

    public function __construct(ContainerInterface $container, App $app, array $moduleConfigs)
    {
        $this->container = $container;
        $this->app = $app;
        $this->modules = [];
        
        foreach ($moduleConfigs as $moduleConfig) {
            // Formato antigo: apenas string com nome da classe
            if (is_string($moduleConfig)) {
                $moduleClass = $moduleConfig;
            }
            // Formato novo: array com configuração
            elseif (is_array($moduleConfig)) {
                // Ignorar módulos remotos (não carregam rotas localmente)
                if (($moduleConfig['mode'] ?? 'local') === 'remote') {
                    continue;
                }
                
                if (!isset($moduleConfig['class'])) {
                    throw new \RuntimeException("Configuração de módulo deve conter 'class'");
                }
                
                $moduleClass = $moduleConfig['class'];
            } else {
                throw new \RuntimeException("Configuração de módulo inválida. Deve ser string ou array");
            }
            
            if (!class_exists($moduleClass)) {
                throw new \RuntimeException("Módulo não encontrado: {$moduleClass}");
            }
            
            if (!is_subclass_of($moduleClass, ModuleInterface::class)) {
                throw new \RuntimeException("Módulo {$moduleClass} deve implementar ModuleInterface");
            }
            
            $this->modules[] = new $moduleClass();
        }
    }

    public function load(): void
    {
        foreach ($this->modules as $module) {
            $module->register($this->container);
        }
        
        foreach ($this->modules as $module) {
            $module->boot();
        }
        
        foreach ($this->modules as $module) {
            $module->routes($this->app);
        }
    }

    public function getModules(): array
    {
        return $this->modules;
    }
}

