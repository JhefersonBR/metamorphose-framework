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

    public function __construct(ContainerInterface $container, App $app, array $moduleClasses)
    {
        $this->container = $container;
        $this->app = $app;
        $this->modules = [];
        
        foreach ($moduleClasses as $moduleClass) {
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

