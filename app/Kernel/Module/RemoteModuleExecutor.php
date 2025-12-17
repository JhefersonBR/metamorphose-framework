<?php

namespace Metamorphose\Kernel\Module;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Context\RequestContext;

/**
 * Executor remoto de módulos
 * 
 * Executa módulos configurados como remotos via HTTP.
 * Envia requisição para o microserviço e retorna o resultado.
 */
class RemoteModuleExecutor implements ModuleExecutorInterface
{
    private ContainerInterface $container;
    private ?ClientInterface $httpClient = null;
    private ?RequestFactoryInterface $requestFactory = null;
    private ?StreamFactoryInterface $streamFactory = null;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;
    private RequestContext $requestContext;

    public function __construct(
        ContainerInterface $container,
        TenantContext $tenantContext,
        UnitContext $unitContext,
        RequestContext $requestContext
    ) {
        $this->container = $container;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
        $this->requestContext = $requestContext;
    }

    /**
     * Obtém cliente HTTP (lazy loading)
     */
    private function getHttpClient(): ClientInterface
    {
        if ($this->httpClient === null) {
            if (!$this->container->has(\Psr\Http\Client\ClientInterface::class)) {
                throw new \RuntimeException(
                    'HTTP Client não configurado. Para usar módulos remotos, instale um pacote PSR-18 (ex: guzzlehttp/guzzle) e registre no container.'
                );
            }
            $this->httpClient = $this->container->get(\Psr\Http\Client\ClientInterface::class);
        }
        return $this->httpClient;
    }

    /**
     * Obtém factory de requisições (lazy loading)
     */
    private function getRequestFactory(): RequestFactoryInterface
    {
        if ($this->requestFactory === null) {
            if (!$this->container->has(\Psr\Http\Message\RequestFactoryInterface::class)) {
                throw new \RuntimeException(
                    'RequestFactory não configurado. Para usar módulos remotos, instale um pacote PSR-17 (ex: guzzlehttp/psr7) e registre no container.'
                );
            }
            $this->requestFactory = $this->container->get(\Psr\Http\Message\RequestFactoryInterface::class);
        }
        return $this->requestFactory;
    }

    /**
     * Obtém factory de streams (lazy loading)
     */
    private function getStreamFactory(): StreamFactoryInterface
    {
        if ($this->streamFactory === null) {
            if (!$this->container->has(\Psr\Http\Message\StreamFactoryInterface::class)) {
                throw new \RuntimeException(
                    'StreamFactory não configurado. Para usar módulos remotos, instale um pacote PSR-17 (ex: guzzlehttp/psr7) e registre no container.'
                );
            }
            $this->streamFactory = $this->container->get(\Psr\Http\Message\StreamFactoryInterface::class);
        }
        return $this->streamFactory;
    }

    /**
     * Executa uma ação de um módulo remotamente
     * 
     * @param string $moduleName Nome do módulo
     * @param string $action Nome da ação/método
     * @param array $payload Dados para a ação
     * @return mixed Resultado da execução
     * @throws \RuntimeException Se o módulo não estiver configurado como remoto ou a execução falhar
     */
    public function execute(string $moduleName, string $action, array $payload = []): mixed
    {
        $moduleConfig = $this->getModuleConfig($moduleName);
        
        if (!isset($moduleConfig['endpoint'])) {
            throw new \RuntimeException(
                "Módulo '{$moduleName}' não possui endpoint configurado para execução remota"
            );
        }

        $endpoint = rtrim($moduleConfig['endpoint'], '/') . '/module/execute';
        $timeout = $moduleConfig['timeout'] ?? 30;
        $headers = $moduleConfig['headers'] ?? [];

        // Construir payload padronizado
        $requestPayload = [
            'module' => $moduleName,
            'action' => $action,
            'context' => [
                'tenant_id' => $this->tenantContext->getTenantId(),
                'tenant_code' => $this->tenantContext->getTenantCode(),
                'unit_id' => $this->unitContext->getUnitId(),
                'unit_code' => $this->unitContext->getUnitCode(),
                'request_id' => $this->requestContext->getRequestId(),
                'user_id' => $this->requestContext->getUserId(),
            ],
            'payload' => $payload,
        ];

        try {
            // Criar requisição HTTP
            $request = $this->getRequestFactory()->createRequest('POST', $endpoint);
            $request = $request->withHeader('Content-Type', 'application/json');
            
            // Adicionar headers customizados
            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            // Adicionar body
            $body = $this->getStreamFactory()->createStream(json_encode($requestPayload, JSON_UNESCAPED_UNICODE));
            $request = $request->withBody($body);

            // Enviar requisição
            $response = $this->getHttpClient()->sendRequest($request);

            // Verificar status code
            if ($response->getStatusCode() !== 200) {
                $errorBody = $response->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
                $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
                
                throw new \RuntimeException(
                    "Erro ao executar módulo remoto '{$moduleName}': {$errorMessage}",
                    $response->getStatusCode()
                );
            }

            // Decodificar resposta
            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (!isset($responseData['success']) || !$responseData['success']) {
                $errorMessage = $responseData['error'] ?? $responseData['message'] ?? 'Erro desconhecido';
                throw new \RuntimeException(
                    "Erro na execução do módulo '{$moduleName}': {$errorMessage}"
                );
            }

            return $responseData['data'] ?? null;

        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new \RuntimeException(
                "Erro de comunicação com módulo remoto '{$moduleName}': " . $e->getMessage(),
                0,
                $e
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Erro ao executar módulo remoto '{$moduleName}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Obtém configuração do módulo
     * 
     * @param string $moduleName Nome do módulo
     * @return array Configuração do módulo
     * @throws \RuntimeException Se o módulo não estiver configurado
     */
    private function getModuleConfig(string $moduleName): array
    {
        $modulesConfig = $this->container->get('config.modules');
        
        if (!isset($modulesConfig['enabled'])) {
            throw new \RuntimeException("Nenhum módulo habilitado na configuração");
        }

        foreach ($modulesConfig['enabled'] as $moduleConfig) {
            if (is_array($moduleConfig)) {
                $configName = $moduleConfig['name'] ?? $this->extractModuleName($moduleConfig['class'] ?? '');
                if ($configName === $moduleName) {
                    if (($moduleConfig['mode'] ?? 'local') !== 'remote') {
                        throw new \RuntimeException(
                            "Módulo '{$moduleName}' não está configurado como remoto"
                        );
                    }
                    return $moduleConfig;
                }
            }
        }

        throw new \RuntimeException("Módulo '{$moduleName}' não encontrado na configuração");
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
}

