<?php

namespace Metamorphose\Modules\Example\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller de exemplo
 */
#[OA\Tag(name: "Example", description: "Módulo de exemplo")]
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

    #[OA\Get(
        path: "/example",
        summary: "Lista exemplos",
        tags: ["Example"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de exemplos",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Hello from Example Module!"),
                        new OA\Property(property: "request_id", type: "string", example: "a1b2c3d4e5f6..."),
                        new OA\Property(property: "tenant_id", type: "string", nullable: true, example: null),
                        new OA\Property(property: "unit_id", type: "string", nullable: true, example: null),
                    ]
                )
            )
        ]
    )]
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

    #[OA\Get(
        path: "/example/{id}",
        summary: "Detalhes de um exemplo",
        tags: ["Example"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID do exemplo",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalhes do exemplo",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Example item details"),
                        new OA\Property(property: "id", type: "string", example: "123"),
                        new OA\Property(property: "request_id", type: "string", example: "a1b2c3d4e5f6..."),
                        new OA\Property(property: "tenant_id", type: "string", nullable: true, example: null),
                        new OA\Property(property: "unit_id", type: "string", nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Exemplo não encontrado")
        ]
    )]
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

