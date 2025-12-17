<?php

namespace Metamorphose\Kernel\Module;

use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Context\RequestContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller para executar módulos remotamente
 * 
 * Entrypoint HTTP genérico que recebe requisições para executar
 * ações de módulos. Usado quando um módulo roda como microserviço.
 */
class ModuleExecuteController
{
    private ContainerInterface $container;
    private LocalModuleExecutor $executor;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;
    private RequestContext $requestContext;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->executor = new LocalModuleExecutor($container);
        $this->tenantContext = $container->get(TenantContext::class);
        $this->unitContext = $container->get(UnitContext::class);
        $this->requestContext = $container->get(RequestContext::class);
    }

    /**
     * Executa uma ação de módulo
     * 
     * @param ServerRequestInterface $request Requisição HTTP
     * @param ResponseInterface $response Resposta HTTP
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        
        if (!is_array($body)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Body da requisição deve ser um JSON válido'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validar campos obrigatórios
        if (!isset($body['module']) || !isset($body['action'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Campos "module" e "action" são obrigatórios'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $moduleName = $body['module'];
        $action = $body['action'];
        $payload = $body['payload'] ?? [];
        $context = $body['context'] ?? [];

        // Aplicar contexto da requisição
        $this->applyContext($context);

        try {
            // Executar ação do módulo
            $result = $this->executor->execute($moduleName, $action, $payload);

            // Retornar sucesso
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\RuntimeException $e) {
            // Erro na execução
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // Erro inesperado
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Aplica contexto da requisição remota
     * 
     * @param array $context Dados de contexto
     */
    private function applyContext(array $context): void
    {
        if (isset($context['tenant_id'])) {
            $this->tenantContext->setTenantId($context['tenant_id']);
        }
        if (isset($context['tenant_code'])) {
            $this->tenantContext->setTenantCode($context['tenant_code']);
        }
        if (isset($context['unit_id'])) {
            $this->unitContext->setUnitId($context['unit_id']);
        }
        if (isset($context['unit_code'])) {
            $this->unitContext->setUnitCode($context['unit_code']);
        }
        if (isset($context['request_id'])) {
            // RequestContext já tem um request_id, mas podemos preservar o original se necessário
            // Por enquanto, mantemos o gerado localmente
        }
        if (isset($context['user_id'])) {
            $this->requestContext->setUserId($context['user_id']);
        }
    }
}

