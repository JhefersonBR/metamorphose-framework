<?php

namespace Metamorphose\Bootstrap;

use Metamorphose\Kernel\Module\ModuleLoader;
use Metamorphose\Kernel\Swagger\SwaggerUIController;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Carrega rotas dos módulos
 */
function loadRoutes(App $app, ContainerInterface $container): void
{
    // Registrar rotas do Swagger UI
    $swaggerJsonPath = __DIR__ . '/../../public/swagger.json';
    $swaggerUiPath = __DIR__ . '/../../public/swagger-ui';
    $swaggerController = new SwaggerUIController($swaggerJsonPath, $swaggerUiPath);
    
    $app->get('/swagger-ui', [$swaggerController, 'ui']);
    $app->get('/swagger.json', [$swaggerController, 'json']);
    
    // Carregar rotas dos módulos
    $moduleClasses = $container->get('config.modules')['enabled'] ?? [];
    
    $loader = new ModuleLoader($container, $app, $moduleClasses);
    $loader->load();
}

