<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;

/**
 * Executor local de módulos
 * 
 * Executa módulos diretamente no mesmo processo (monolítico).
 * Resolve o módulo via ModuleLoader e chama o método diretamente.
 */
class LocalModuleExecutor implements ModuleExecutorInterface
{
    private ContainerInterface $container;
    private array $moduleInstances = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Executa uma ação de um módulo localmente
     * 
     * @param string $moduleName Nome do módulo
     * @param string $action Nome da ação/método
     * @param array $payload Dados para a ação
     * @return mixed Resultado da execução
     * @throws \RuntimeException Se o módulo não existir ou a ação falhar
     */
    public function execute(string $moduleName, string $action, array $payload = []): mixed
    {
        $module = $this->getModule($moduleName);
        
        if (!method_exists($module, $action)) {
            throw new \RuntimeException(
                "Ação '{$action}' não encontrada no módulo '{$moduleName}'"
            );
        }

        try {
            return $module->$action($payload);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Erro ao executar ação '{$action}' no módulo '{$moduleName}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Obtém instância do módulo
     * 
     * @param string $moduleName Nome do módulo
     * @return object Instância do módulo
     * @throws \RuntimeException Se o módulo não existir
     */
    private function getModule(string $moduleName): object
    {
        if (isset($this->moduleInstances[$moduleName])) {
            return $this->moduleInstances[$moduleName];
        }

        $modulesConfig = $this->container->get('config.modules');
        
        // Buscar módulo na configuração
        $moduleClass = null;
        if (isset($modulesConfig['enabled'])) {
            foreach ($modulesConfig['enabled'] as $moduleConfig) {
                if (is_string($moduleConfig)) {
                    // Formato antigo: apenas classe
                    $className = $this->extractModuleName($moduleConfig);
                    if ($className === $moduleName) {
                        $moduleClass = $moduleConfig;
                        break;
                    }
                } elseif (is_array($moduleConfig)) {
                    // Formato novo: array com configuração
                    $configName = $moduleConfig['name'] ?? $this->extractModuleName($moduleConfig['class'] ?? '');
                    if ($configName === $moduleName && isset($moduleConfig['class'])) {
                        $moduleClass = $moduleConfig['class'];
                        break;
                    }
                }
            }
        }

        if ($moduleClass === null || !class_exists($moduleClass)) {
            throw new \RuntimeException("Módulo '{$moduleName}' não encontrado ou não habilitado");
        }

        if (!is_subclass_of($moduleClass, ModuleInterface::class)) {
            throw new \RuntimeException("Classe '{$moduleClass}' não implementa ModuleInterface");
        }

        $this->moduleInstances[$moduleName] = new $moduleClass();
        return $this->moduleInstances[$moduleName];
    }

    /**
     * Extrai nome do módulo a partir do nome da classe
     * 
     * @param string $className Nome completo da classe
     * @return string Nome do módulo
     */
    private function extractModuleName(string $className): string
    {
        // Exemplo: Metamorphose\Modules\Permission\Module -> permission
        $parts = explode('\\', $className);
        if (count($parts) >= 3 && $parts[1] === 'Modules') {
            return strtolower($parts[2]);
        }
        return strtolower(basename(str_replace('\\', '/', $className)));
    }
}

