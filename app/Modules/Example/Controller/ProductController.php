<?php

namespace Metamorphose\Modules\Example\Controller;

use Metamorphose\Modules\Example\Model\Product;
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller para gerenciar produtos
 */
#[OA\Tag(name: "Products", description: "Gerenciamento de produtos")]
class ProductController
{
    #[OA\Get(
        path: "/products",
        summary: "Lista todos os produtos",
        tags: ["Products"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de produtos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "name", type: "string"),
                            new OA\Property(property: "description", type: "string"),
                            new OA\Property(property: "price", type: "number", format: "float"),
                        ]
                    )
                )
            )
        ]
    )]
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $criteria = new QueryCriteria();
        
        // Filtros opcionais da query string
        $queryParams = $request->getQueryParams();
        
        if (isset($queryParams['name'])) {
            $criteria->addFilter('name', 'LIKE', '%' . $queryParams['name'] . '%');
        }
        
        if (isset($queryParams['price_min'])) {
            $criteria->addFilter('price', '>=', (float) $queryParams['price_min']);
        }
        
        if (isset($queryParams['price_max'])) {
            $criteria->addFilter('price', '<=', (float) $queryParams['price_max']);
        }
        
        $criteria->orderBy('created_at', 'DESC');
        
        $products = Product::load($criteria);
        $data = array_map(fn($p) => $p->toArray(), $products);
        
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: "/products/{id}",
        summary: "Busca produto por ID",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Produto encontrado"),
            new OA\Response(response: 404, description: "Produto não encontrado")
        ]
    )]
    public function show(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);
        
        $product = Product::load($id);
        
        if ($product === null) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: "/products",
        summary: "Cria um novo produto",
        tags: ["Products"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Produto criado"),
            new OA\Response(response: 400, description: "Dados inválidos")
        ]
    )]
    public function create(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody();
        
        if (empty($data['name']) || empty($data['price'])) {
            $response->getBody()->write(json_encode(['error' => 'Name and price are required']));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }
        
        $product = new Product();
        $product->fromArray([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => (float) $data['price'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $product->store();
        
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }

    #[OA\Put(
        path: "/products/{id}",
        summary: "Atualiza um produto",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "price", type: "number", format: "float"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Produto atualizado"),
            new OA\Response(response: 404, description: "Produto não encontrado")
        ]
    )]
    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);
        
        $product = Product::load($id);
        
        if ($product === null) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
        
        $data = $request->getParsedBody();
        
        if (isset($data['name'])) {
            $product->set('name', $data['name']);
        }
        if (isset($data['description'])) {
            $product->set('description', $data['description']);
        }
        if (isset($data['price'])) {
            $product->set('price', (float) $data['price']);
        }
        
        $product->set('updated_at', date('Y-m-d H:i:s'));
        $product->store();
        
        $response->getBody()->write(json_encode($product->toArray(), JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Delete(
        path: "/products/{id}",
        summary: "Deleta um produto",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Produto deletado"),
            new OA\Response(response: 404, description: "Produto não encontrado")
        ]
    )]
    public function delete(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $id = (int) ($args['id'] ?? 0);
        
        $product = Product::load($id);
        
        if ($product === null) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
        
        $product->delete();
        
        return $response->withStatus(204);
    }
}

