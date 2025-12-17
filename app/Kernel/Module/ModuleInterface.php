<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Interface para módulos do sistema
 * 
 * Cada módulo deve implementar esta interface para ser carregado pelo ModuleLoader.
 * Módulos não sabem se estão rodando em monólito ou microserviço.
 */
interface ModuleInterface
{
    /**
     * Registra serviços no container
     */
    public function register(ContainerInterface $container): void;

    /**
     * Executa inicializações após o registro
     */
    public function boot(): void;

    /**
     * Registra rotas da aplicação
     */
    public function routes(App $app): void;
}

