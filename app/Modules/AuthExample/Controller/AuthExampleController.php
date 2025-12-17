<?php

namespace Metamorphose\Modules\AuthExample\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller principal do módulo AuthExample
 */
class AuthExampleController
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
            'message' => 'Hello from AuthExample Module!',
            'request_id' => $this->requestContext->getRequestId(),
        ];
        
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
