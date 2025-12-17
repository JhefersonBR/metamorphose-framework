<?php

namespace Metamorphose\Modules\AuthExample;

use Metamorphose\Kernel\Module\ModuleInterface;
use Metamorphose\Modules\AuthExample\Middleware\AuthMiddleware;
use Metamorphose\Modules\AuthExample\Repository\UserRepository;
use Metamorphose\Modules\AuthExample\Service\AuthService;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Módulo AuthExample - Sistema de autenticação JWT
 */
class Module implements ModuleInterface
{
    public function register(ContainerInterface $container): void
    {
        // Registrar UserRepository
        $container->set(
            UserRepository::class,
            function (ContainerInterface $c) {
                return new UserRepository(
                    $c->get(\Metamorphose\Kernel\Database\ConnectionResolverInterface::class)
                );
            }
        );

        // Registrar AuthService
        $container->set(
            AuthService::class,
            function (ContainerInterface $c) {
                $jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production';
                return new AuthService(
                    $c->get(UserRepository::class),
                    $jwtSecret
                );
            }
        );

        // Registrar AuthMiddleware
        $container->set(
            AuthMiddleware::class,
            function (ContainerInterface $c) {
                return new AuthMiddleware(
                    $c->get(AuthService::class)
                );
            }
        );

        // Registrar controllers
        $container->set(
            Controller\AuthController::class,
            function (ContainerInterface $c) {
                return new Controller\AuthController(
                    $c->get(AuthService::class)
                );
            }
        );

        $container->set(
            Controller\ProtectedController::class,
            function (ContainerInterface $c) {
                return new Controller\ProtectedController();
            }
        );
    }

    public function boot(): void
    {
        // Executar inicializações após o registro
    }

    public function routes(App $app): void
    {
        $container = $app->getContainer();
        
        // Rotas públicas de autenticação
        $app->post('/auth/register', Controller\AuthController::class . ':register');
        $app->post('/auth/login', Controller\AuthController::class . ':login');

        // Rotas protegidas (requerem autenticação)
        $app->get('/auth/protected', Controller\ProtectedController::class . ':example')
            ->add($container->get(AuthMiddleware::class));
    }
}
