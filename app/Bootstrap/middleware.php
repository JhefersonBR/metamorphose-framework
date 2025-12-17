<?php

namespace Metamorphose\Bootstrap;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Log\HttpLogMiddleware;
use Metamorphose\Kernel\Log\LoggerInterface;
use Metamorphose\Kernel\Log\LogContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;

/**
 * Registra middlewares globais
 */
function registerMiddlewares(App $app, ContainerInterface $container): void
{
    $app->add(new ContextMiddleware($container));
    
    $logConfig = $container->get('config.log');
    if ($logConfig['http_log']) {
        $app->add(new HttpLogMiddleware(
            $container->get(LoggerInterface::class),
            $container->get(LogContext::class),
            $logConfig['http_log']
        ));
    }
}

/**
 * Middleware para preencher contextos
 */
class ContextMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestContext = $this->container->get(RequestContext::class);
        $tenantContext = $this->container->get(TenantContext::class);
        $unitContext = $this->container->get(UnitContext::class);
        
        $this->populateRequestContext($request, $requestContext);
        $this->populateTenantContext($request, $tenantContext);
        $this->populateUnitContext($request, $unitContext);
        
        return $handler->handle($request);
    }

    private function populateRequestContext(
        ServerRequestInterface $request,
        RequestContext $context
    ): void {
        $attributes = $request->getAttributes();
        
        if (isset($attributes['user_id'])) {
            $context->setUserId($attributes['user_id']);
        }
        
        $context->setRequestData([
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);
    }

    private function populateTenantContext(
        ServerRequestInterface $request,
        TenantContext $context
    ): void {
        $tenantId = $request->getHeaderLine('X-Tenant-ID');
        
        if (empty($tenantId)) {
            $tenantId = $request->getQueryParams()['tenant_id'] ?? null;
        }
        
        if (!empty($tenantId)) {
            $context->setTenantId($tenantId);
            $context->setTenantCode($request->getHeaderLine('X-Tenant-Code') ?: null);
        }
    }

    private function populateUnitContext(
        ServerRequestInterface $request,
        UnitContext $context
    ): void {
        $unitId = $request->getHeaderLine('X-Unit-ID');
        
        if (empty($unitId)) {
            $unitId = $request->getQueryParams()['unit_id'] ?? null;
        }
        
        if (!empty($unitId)) {
            $context->setUnitId($unitId);
            $context->setUnitCode($request->getHeaderLine('X-Unit-Code') ?: null);
        }
    }
}

