<?php

namespace Metamorphose\Bootstrap;

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Cria e configura a aplicação Slim
 */
function createApp(ContainerInterface $container): App
{
    AppFactory::setContainer($container);
    $app = AppFactory::create();
    
    // Adicionar error handler customizado
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->forceContentType('application/json');
    
    return $app;
}

