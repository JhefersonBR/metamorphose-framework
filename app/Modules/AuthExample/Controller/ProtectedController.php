<?php

namespace Metamorphose\Modules\AuthExample\Controller;

use Metamorphose\Modules\AuthExample\Entity\User;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller com rotas protegidas (requer autenticação)
 */
#[OA\Tag(name: "Auth", description: "Rotas protegidas")]
class ProtectedController
{
    #[OA\Get(
        path: "/auth/protected",
        summary: "Rota protegida (requer autenticação JWT)",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Acesso autorizado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "This is a protected route"),
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "timestamp", type: "string", format: "date-time"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Não autorizado - Token inválido ou ausente"),
        ]
    )]
    public function example(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Obter usuário do request (adicionado pelo AuthMiddleware)
        $user = $request->getAttribute('user');
        
        if (!$user instanceof User) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'User not found in request',
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'message' => 'This is a protected route',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }
}

