<?php

namespace Metamorphose\Modules\Example;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Módulo Example
 * 
 * Módulo de exemplo demonstrando a estrutura básica de um módulo.
 */
class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar serviços do módulo aqui
        // O ExampleController será resolvido automaticamente via autowiring do PHP-DI
    }

    public function boot(): void
    {
        // Executar inicializações após o registro
    }

    public function routes(App $app): void
    {
        $app->get('/example', \Metamorphose\Modules\Example\Controller\ExampleController::class . ':index');
        $app->get('/example/{id}', \Metamorphose\Modules\Example\Controller\ExampleController::class . ':show');
    }
}

