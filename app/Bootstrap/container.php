<?php

namespace Metamorphose\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Database\ConnectionResolver;
use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use Metamorphose\Kernel\Log\LoggerFactory;
use Metamorphose\Kernel\Log\LoggerInterface;
use Metamorphose\Kernel\Log\LogContext;
use Metamorphose\Kernel\Permission\PermissionResolver;
use Metamorphose\Kernel\Permission\PermissionService;

/**
 * Configuração do container PSR-11
 * 
 * Registra todos os serviços do kernel e suas dependências.
 */
function buildContainer(): Container
{
    $builder = new ContainerBuilder();
    $builder->useAutowiring(true);
    
    $config = [
        'app' => require __DIR__ . '/../../config/app.php',
        'database' => require __DIR__ . '/../../config/database.php',
        'log' => require __DIR__ . '/../../config/log.php',
        'modules' => require __DIR__ . '/../../config/modules.php',
    ];
    
    $builder->addDefinitions([
        'config' => $config,
        'config.app' => $config['app'],
        'config.database' => $config['database'],
        'config.log' => $config['log'],
        'config.modules' => $config['modules'],
        
        RequestContext::class => \DI\create(RequestContext::class),
        TenantContext::class => \DI\create(TenantContext::class),
        UnitContext::class => \DI\create(UnitContext::class),
        
        ConnectionResolverInterface::class => \DI\factory(function (Container $c) {
            return new ConnectionResolver(
                $c->get('config.database'),
                $c->get(TenantContext::class),
                $c->get(UnitContext::class)
            );
        }),
        
        LoggerInterface::class => \DI\factory(function (Container $c) {
            $factory = new LoggerFactory($c->get('config.log'));
            return $factory->create();
        }),
        
        LogContext::class => \DI\factory(function (Container $c) {
            return new LogContext(
                $c->get(RequestContext::class),
                $c->get(TenantContext::class),
                $c->get(UnitContext::class)
            );
        }),
        
        PermissionResolver::class => \DI\factory(function (Container $c) {
            return new PermissionResolver(
                $c->get(TenantContext::class),
                $c->get(UnitContext::class)
            );
        }),
        
        PermissionService::class => \DI\factory(function (Container $c) {
            return new PermissionService(
                $c->get(PermissionResolver::class),
                $c->get(TenantContext::class),
                $c->get(UnitContext::class)
            );
        }),
    ]);
    
    return $builder->build();
}

