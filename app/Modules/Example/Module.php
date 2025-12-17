<?php

namespace Metamorphose\Modules\Example;

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Módulo Example
 */
class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar serviços do módulo aqui
    }

    public function boot(): void
    {
        // Executar inicializações após o registro
    }

    public function routes(App $app): void
    {
        $app->get('/example', Controller\ExampleController::class . ':index');
    }
}
