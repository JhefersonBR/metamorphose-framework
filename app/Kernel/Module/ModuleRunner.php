<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;

/**
 * Facade para execução de módulos
 * 
 * Ponto único de execução de módulos. Decide se o módulo
 * deve ser executado localmente ou remotamente baseado na configuração.
 */
class ModuleRunner
{
    private ContainerInterface $container;
    private ?LocalModuleExecutor $localExecutor = null;
    private ?RemoteModuleExecutor $remoteExecutor = null;
    private array $moduleModes = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->loadModuleModes();
    }

    /**
     * Executa uma ação de um módulo
     * 
     * Decide automaticamente se deve executar localmente ou remotamente
     * baseado na configuração do módulo.
     * 
     * @param string $moduleName Nome do módulo
     * @param string $action Nome da ação/método
     * @param array $payload Dados para a ação
     * @return mixed Resultado da execução
     * @throws \RuntimeException Se o módulo não existir ou a execução falhar
     */
    public function execute(string $moduleName, string $action, array $payload = []): mixed
    {
        $mode = $this->getModuleMode($moduleName);
        
        if ($mode === 'remote') {
            return $this->getRemoteExecutor()->execute($moduleName, $action, $payload);
        }
        
        return $this->getLocalExecutor()->execute($moduleName, $action, $payload);
    }

    /**
     * Obtém o modo de execução do módulo (local ou remote)
     * 
     * @param string $moduleName Nome do módulo
     * @return string 'local' ou 'remote'
     * @throws \RuntimeException Se o módulo não estiver configurado
     */
    private function getModuleMode(string $moduleName): string
    {
        if (isset($this->moduleModes[$moduleName])) {
            return $this->moduleModes[$moduleName];
        }

        throw new \RuntimeException("Módulo '{$moduleName}' não encontrado na configuração");
    }

    /**
     * Carrega os modos de execução dos módulos da configuração
     */
    private function loadModuleModes(): void
    {
        $modulesConfig = $this->container->get('config.modules');
        
        if (!isset($modulesConfig['enabled'])) {
            return;
        }

        foreach ($modulesConfig['enabled'] as $moduleConfig) {
            if (is_string($moduleConfig)) {
                // Formato antigo: apenas classe (assume local)
                $moduleName = $this->extractModuleName($moduleConfig);
                $this->moduleModes[$moduleName] = 'local';
            } elseif (is_array($moduleConfig)) {
                // Formato novo: array com configuração
                $moduleName = $moduleConfig['name'] ?? $this->extractModuleName($moduleConfig['class'] ?? '');
                $this->moduleModes[$moduleName] = $moduleConfig['mode'] ?? 'local';
            }
        }
    }

    /**
     * Extrai nome do módulo a partir do nome da classe
     * 
     * @param string $className Nome completo da classe
     * @return string Nome do módulo
     */
    private function extractModuleName(string $className): string
    {
        $parts = explode('\\', $className);
        if (count($parts) >= 3 && $parts[1] === 'Modules') {
            return strtolower($parts[2]);
        }
        return strtolower(basename(str_replace('\\', '/', $className)));
    }

    /**
     * Obtém executor local (lazy loading)
     */
    private function getLocalExecutor(): LocalModuleExecutor
    {
        if ($this->localExecutor === null) {
            $this->localExecutor = new LocalModuleExecutor($this->container);
        }
        return $this->localExecutor;
    }

    /**
     * Obtém executor remoto (lazy loading)
     */
    private function getRemoteExecutor(): RemoteModuleExecutor
    {
        if ($this->remoteExecutor === null) {
            $this->remoteExecutor = new RemoteModuleExecutor(
                $this->container,
                $this->container->get(\Metamorphose\Kernel\Context\TenantContext::class),
                $this->container->get(\Metamorphose\Kernel\Context\UnitContext::class),
                $this->container->get(\Metamorphose\Kernel\Context\RequestContext::class)
            );
        }
        return $this->remoteExecutor;
    }
}

