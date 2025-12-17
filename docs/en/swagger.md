# Swagger Integration

Metamorphose Framework includes built-in Swagger/OpenAPI integration for automatic API documentation generation.

## Overview

The framework uses:
- **swagger-php**: For generating OpenAPI specifications from annotations
- **Swagger UI**: For interactive API documentation
- **CLI Command**: For generating/updating documentation

## Installation

Swagger dependencies are already included in `composer.json`. After installing dependencies:

```bash
composer install
```

## Generating Documentation

### Initial Generation

Generate Swagger documentation for the first time:

```bash
php bin/metamorphose swagger:generate
```

This command:
1. Scans all modules for OpenAPI annotations
2. Generates `public/swagger.json`
3. Makes documentation available at `/swagger-ui`

### Updating Documentation

Run the same command whenever you add or modify API endpoints:

```bash
php bin/metamorphose swagger:generate
```

## Accessing Documentation

After generating documentation, access it at:

- **Swagger UI**: `http://localhost/swagger-ui`
- **OpenAPI JSON**: `http://localhost/swagger.json`

## Documenting Your API

### Basic Controller Documentation

Add OpenAPI attributes to your controllers:

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Tag(name: "Products", description: "Product management endpoints")]
class ProductController
{
    #[OA\Get(
        path: "/products",
        summary: "List all products",
        tags: ["Products"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of products",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Product Name"),
                            new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
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
        // Implementation
    }
}
```

### Documenting Parameters

```php
#[OA\Get(
    path: "/products/{id}",
    summary: "Get product by ID",
    tags: ["Products"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "Product ID",
            schema: new OA\Schema(type: "integer")
        ),
        new OA\Parameter(
            name: "include",
            in: "query",
            required: false,
            description: "Include related resources",
            schema: new OA\Schema(type: "string", example: "category,reviews")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Product details",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ]
            )
        ),
        new OA\Response(response: 404, description: "Product not found")
    ]
)]
public function show(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    // Implementation
}
```

### Documenting POST Requests

```php
#[OA\Post(
    path: "/products",
    summary: "Create a new product",
    tags: ["Products"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string", example: "New Product"),
                new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
                new OA\Property(property: "description", type: "string", example: "Product description"),
            ],
            required: ["name", "price"]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Product created",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ]
            )
        ),
        new OA\Response(response: 400, description: "Invalid input"),
        new OA\Response(response: 422, description: "Validation error")
    ]
)]
public function create(
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    // Implementation
}
```

### Documenting Authentication

```php
#[OA\Get(
    path: "/products",
    summary: "List products",
    tags: ["Products"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Success"),
        new OA\Response(response: 401, description: "Unauthorized")
    ]
)]
public function index(...): ResponseInterface
{
    // Implementation
}
```

### Documenting Headers

```php
#[OA\Get(
    path: "/products",
    summary: "List products",
    tags: ["Products"],
    parameters: [
        new OA\Parameter(
            name: "X-Tenant-ID",
            in: "header",
            required: false,
            description: "Tenant identifier",
            schema: new OA\Schema(type: "string", example: "tenant-123")
        ),
        new OA\Parameter(
            name: "X-Unit-ID",
            in: "header",
            required: false,
            description: "Unit identifier",
            schema: new OA\Schema(type: "string", example: "unit-456")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Success")
    ]
)]
```

## Common Attributes

### Response Types

```php
// Simple response
new OA\Response(response: 200, description: "Success")

// JSON response with schema
new OA\Response(
    response: 200,
    description: "Success",
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: "id", type: "integer"),
            new OA\Property(property: "name", type: "string"),
        ]
    )
)

// Array response
new OA\Response(
    response: 200,
    description: "List",
    content: new OA\JsonContent(
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/Product")
    )
)
```

### Property Types

```php
new OA\Property(property: "id", type: "integer", example: 1)
new OA\Property(property: "name", type: "string", example: "Product Name")
new OA\Property(property: "price", type: "number", format: "float", example: 99.99)
new OA\Property(property: "is_active", type: "boolean", example: true)
new OA\Property(property: "created_at", type: "string", format: "date-time")
new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string"))
```

## Best Practices

1. **Document all endpoints**: Add OpenAPI attributes to every public method
2. **Use descriptive summaries**: Clear, concise descriptions help developers
3. **Include examples**: Examples make documentation more useful
4. **Document errors**: Include all possible error responses
5. **Use tags**: Group related endpoints with tags
6. **Keep updated**: Regenerate documentation after changes

## Example: Complete Controller

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Tag(name: "Products", description: "Product management")]
class ProductController
{
    #[OA\Get(
        path: "/products",
        summary: "List products",
        tags: ["Products"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product list",
                content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Product"))
            )
        ]
    )]
    public function index(...): ResponseInterface { }

    #[OA\Post(
        path: "/products",
        summary: "Create product",
        tags: ["Products"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: "#/components/schemas/ProductInput")
        ),
        responses: [
            new OA\Response(response: 201, description: "Created", content: new OA\JsonContent(ref: "#/components/schemas/Product")),
            new OA\Response(response: 400, description: "Bad request")
        ]
    )]
    public function create(...): ResponseInterface { }
}
```

## Troubleshooting

### Documentation Not Updating

If changes don't appear:
1. Regenerate: `php bin/metamorphose swagger:generate`
2. Clear browser cache
3. Check annotations syntax

### Missing Endpoints

If endpoints don't appear:
1. Verify annotations are correct
2. Check file is being scanned
3. Verify namespace matches

### Errors in Generation

If generation fails:
1. Check PHP syntax errors
2. Verify OpenAPI attribute syntax
3. Check file permissions

## Next Steps

- Read about [Modules](modules.md) for module development
- Learn about [Architecture](architecture.md) for system design
- Check [Getting Started](getting-started.md) for examples

