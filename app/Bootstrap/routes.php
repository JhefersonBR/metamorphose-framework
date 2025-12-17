<?php

namespace Metamorphose\Bootstrap;

use Metamorphose\Kernel\Module\ModuleLoader;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Carrega rotas dos módulos
 */
function loadRoutes(App $app, ContainerInterface $container): void
{
    $moduleClasses = $container->get('config.modules')['enabled'] ?? [];
    
    $loader = new ModuleLoader($container, $app, $moduleClasses);
    $loader->load();
}

