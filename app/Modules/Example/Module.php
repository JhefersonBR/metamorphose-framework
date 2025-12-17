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
        // Rotas de exemplo
        $app->get('/example', Controller\ExampleController::class . ':index');
        
        // Rotas de produtos (CRUD completo)
        $app->get('/products', Controller\ProductController::class . ':index');
        $app->get('/products/{id}', Controller\ProductController::class . ':show');
        $app->post('/products', Controller\ProductController::class . ':create');
        $app->put('/products/{id}', Controller\ProductController::class . ':update');
        $app->delete('/products/{id}', Controller\ProductController::class . ':delete');
    }
}
