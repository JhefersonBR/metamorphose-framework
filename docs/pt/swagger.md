# Integração Swagger

O Metamorphose Framework inclui integração nativa com Swagger/OpenAPI para geração automática de documentação de API.

## Visão Geral

O framework usa:
- **swagger-php**: Para gerar especificações OpenAPI a partir de anotações
- **Swagger UI**: Para documentação interativa da API
- **Comando CLI**: Para gerar/atualizar documentação

## Instalação

As dependências do Swagger já estão incluídas no `composer.json`. Após instalar as dependências:

```bash
composer install
```

## Gerando Documentação

### Geração Inicial

Gere a documentação Swagger pela primeira vez:

```bash
php bin/metamorphose swagger:generate
```

Este comando:
1. Escaneia todos os módulos procurando por anotações OpenAPI
2. Gera `public/swagger.json`
3. Disponibiliza documentação em `/swagger-ui`

### Atualizando Documentação

Execute o mesmo comando sempre que adicionar ou modificar endpoints de API:

```bash
php bin/metamorphose swagger:generate
```

## Acessando Documentação

Após gerar a documentação, acesse em:

- **Swagger UI**: `http://localhost/swagger-ui`
- **OpenAPI JSON**: `http://localhost/swagger.json`

## Documentando Sua API

### Documentação Básica de Controller

Adicione atributos OpenAPI aos seus controllers:

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Tag(name: "Products", description: "Endpoints de gerenciamento de produtos")]
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
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "Nome do Produto"),
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
        // Implementação
    }
}
```

### Documentando Parâmetros

```php
#[OA\Get(
    path: "/products/{id}",
    summary: "Obter produto por ID",
    tags: ["Products"],
    parameters: [
        new OA\Parameter(
            name: "id",
            in: "path",
            required: true,
            description: "ID do produto",
            schema: new OA\Schema(type: "integer")
        ),
        new OA\Parameter(
            name: "include",
            in: "query",
            required: false,
            description: "Incluir recursos relacionados",
            schema: new OA\Schema(type: "string", example: "category,reviews")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Detalhes do produto",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ]
            )
        ),
        new OA\Response(response: 404, description: "Produto não encontrado")
    ]
)]
public function show(
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    // Implementação
}
```

### Documentando Requisições POST

```php
#[OA\Post(
    path: "/products",
    summary: "Criar um novo produto",
    tags: ["Products"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string", example: "Novo Produto"),
                new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
                new OA\Property(property: "description", type: "string", example: "Descrição do produto"),
            ],
            required: ["name", "price"]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Produto criado",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "number"),
                ]
            )
        ),
        new OA\Response(response: 400, description: "Entrada inválida"),
        new OA\Response(response: 422, description: "Erro de validação")
    ]
)]
public function create(
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    // Implementação
}
```

### Documentando Autenticação

```php
#[OA\Get(
    path: "/products",
    summary: "Lista produtos",
    tags: ["Products"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Sucesso"),
        new OA\Response(response: 401, description: "Não autorizado")
    ]
)]
public function index(...): ResponseInterface
{
    // Implementação
}
```

### Documentando Headers

```php
#[OA\Get(
    path: "/products",
    summary: "Lista produtos",
    tags: ["Products"],
    parameters: [
        new OA\Parameter(
            name: "X-Tenant-ID",
            in: "header",
            required: false,
            description: "Identificador do tenant",
            schema: new OA\Schema(type: "string", example: "tenant-123")
        ),
        new OA\Parameter(
            name: "X-Unit-ID",
            in: "header",
            required: false,
            description: "Identificador da unit",
            schema: new OA\Schema(type: "string", example: "unit-456")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Sucesso")
    ]
)]
```

## Atributos Comuns

### Tipos de Resposta

```php
// Resposta simples
new OA\Response(response: 200, description: "Sucesso")

// Resposta JSON com schema
new OA\Response(
    response: 200,
    description: "Sucesso",
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: "id", type: "integer"),
            new OA\Property(property: "name", type: "string"),
        ]
    )
)

// Resposta array
new OA\Response(
    response: 200,
    description: "Lista",
    content: new OA\JsonContent(
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/Product")
    )
)
```

### Tipos de Propriedades

```php
new OA\Property(property: "id", type: "integer", example: 1)
new OA\Property(property: "name", type: "string", example: "Nome do Produto")
new OA\Property(property: "price", type: "number", format: "float", example: 99.99)
new OA\Property(property: "is_active", type: "boolean", example: true)
new OA\Property(property: "created_at", type: "string", format: "date-time")
new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string"))
```

## Melhores Práticas

1. **Documente todos os endpoints**: Adicione atributos OpenAPI a cada método público
2. **Use resumos descritivos**: Descrições claras e concisas ajudam desenvolvedores
3. **Inclua exemplos**: Exemplos tornam a documentação mais útil
4. **Documente erros**: Inclua todas as possíveis respostas de erro
5. **Use tags**: Agrupe endpoints relacionados com tags
6. **Mantenha atualizado**: Regenere documentação após mudanças

## Exemplo: Controller Completo

```php
<?php

namespace Metamorphose\Modules\Product\Controller;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Tag(name: "Products", description: "Gerenciamento de produtos")]
class ProductController
{
    #[OA\Get(
        path: "/products",
        summary: "Lista produtos",
        tags: ["Products"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de produtos",
                content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/Product"))
            )
        ]
    )]
    public function index(...): ResponseInterface { }

    #[OA\Post(
        path: "/products",
        summary: "Cria produto",
        tags: ["Products"],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: "#/components/schemas/ProductInput")
        ),
        responses: [
            new OA\Response(response: 201, description: "Criado", content: new OA\JsonContent(ref: "#/components/schemas/Product")),
            new OA\Response(response: 400, description: "Requisição inválida")
        ]
    )]
    public function create(...): ResponseInterface { }
}
```

## Solução de Problemas

### Documentação Não Atualiza

Se mudanças não aparecem:
1. Regenere: `php bin/metamorphose swagger:generate`
2. Limpe cache do navegador
3. Verifique sintaxe das anotações

### Endpoints Faltando

Se endpoints não aparecem:
1. Verifique se anotações estão corretas
2. Confirme que arquivo está sendo escaneado
3. Verifique se namespace corresponde

### Erros na Geração

Se geração falha:
1. Verifique erros de sintaxe PHP
2. Confirme sintaxe dos atributos OpenAPI
3. Verifique permissões de arquivo

## Próximos Passos

- Leia sobre [Módulos](modules.md) para desenvolvimento de módulos
- Aprenda sobre [Arquitetura](architecture.md) para design de sistema
- Verifique [Primeiros Passos](getting-started.md) para exemplos

