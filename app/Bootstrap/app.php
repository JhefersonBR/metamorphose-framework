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
    return AppFactory::create();
}

