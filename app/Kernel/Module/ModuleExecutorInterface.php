<?php

namespace Metamorphose\Kernel\Module;

/**
 * Interface para execução de módulos
 * 
 * Define o contrato genérico para executar ações de módulos,
 * sem conhecer implementações concretas ou transporte.
 */
interface ModuleExecutorInterface
{
    /**
     * Executa uma ação de um módulo
     * 
     * @param string $moduleName Nome do módulo (ex: 'permission', 'stock')
     * @param string $action Nome da ação/método a executar
     * @param array $payload Dados para a ação
     * @return mixed Resultado da execução
     * @throws \RuntimeException Se o módulo não existir ou a ação falhar
     */
    public function execute(string $moduleName, string $action, array $payload = []): mixed;
}

