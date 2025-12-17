<?php

namespace Metamorphose\Modules\Example\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller de exemplo
 */
class ExampleController
{
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        RequestContext $requestContext,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->requestContext = $requestContext;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = [
            'message' => 'Hello from Example Module!',
            'request_id' => $this->requestContext->getRequestId(),
            'tenant_id' => $this->tenantContext->getTenantId(),
            'unit_id' => $this->unitContext->getUnitId(),
        ];
        
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $data = [
            'message' => 'Example item details',
            'id' => $args['id'] ?? null,
            'request_id' => $this->requestContext->getRequestId(),
            'tenant_id' => $this->tenantContext->getTenantId(),
            'unit_id' => $this->unitContext->getUnitId(),
        ];
        
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

