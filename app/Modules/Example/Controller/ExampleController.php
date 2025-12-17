<?php

namespace Metamorphose\Modules\Example\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller principal do módulo Example
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
        summary: "Retorna exemplo de resposta JSON",
        tags: ["Example"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resposta de exemplo",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "title", type: "string", example: "Example"),
                        new OA\Property(property: "modulo", type: "string", example: "Example"),
                        new OA\Property(property: "text", type: "string", example: "Only example"),
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
            'title' => 'Example',
            'modulo' => 'Example',
            'text' => 'Only example',
        ];
        
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
