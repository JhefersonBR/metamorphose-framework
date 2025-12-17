<?php

namespace Metamorphose\Bootstrap;

use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Database\DBALConnectionResolver;
use Metamorphose\Kernel\Model\AbstractModel;
use Metamorphose\Kernel\Database\Transaction;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;

/**
 * Cria e configura a aplicação Slim
 */
function createApp(ContainerInterface $container): App
{
    AppFactory::setContainer($container);
    $app = AppFactory::create();
    
    // Adicionar middleware de body parsing para JSON
    $app->addBodyParsingMiddleware();
    
    // Configurar AbstractModel
    if ($container->has(DBALConnectionResolver::class)) {
        AbstractModel::setConnectionResolver($container->get(DBALConnectionResolver::class));
        AbstractModel::setContexts(
            $container->get(TenantContext::class),
            $container->get(UnitContext::class)
        );
    }
    
    // Adicionar error handler customizado
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->forceContentType('application/json');
    
    return $app;
}

