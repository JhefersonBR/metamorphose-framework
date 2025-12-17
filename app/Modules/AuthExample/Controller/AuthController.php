<?php

namespace Metamorphose\Modules\AuthExample\Controller;

use Metamorphose\Modules\AuthExample\Service\AuthService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller de autenticação
 */
#[OA\Tag(name: "Auth", description: "Autenticação JWT")]
class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[OA\Post(
        path: "/auth/register",
        summary: "Registrar novo usuário",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string", example: "johndoe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                ],
                required: ["username", "email", "password"]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuário registrado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "User registered successfully"),
                        new OA\Property(property: "data", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Erro de validação"),
            new OA\Response(response: 409, description: "Usuário ou email já existe"),
        ]
    )]
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            
            if (!isset($body['username']) || !isset($body['email']) || !isset($body['password'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation error',
                    'message' => 'Username, email and password are required',
                ], JSON_PRETTY_PRINT));
                
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $result = $this->authService->register(
                $body['username'],
                $body['email'],
                $body['password']
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $result,
            ], JSON_PRETTY_PRINT));

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(409)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => 'An error occurred while registering the user',
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: "/auth/login",
        summary: "Autenticar usuário",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string", example: "johndoe"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                ],
                required: ["username", "password"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login realizado com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(property: "data", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Erro de validação"),
            new OA\Response(response: 401, description: "Credenciais inválidas"),
        ]
    )]
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            
            if (!isset($body['username']) || !isset($body['password'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation error',
                    'message' => 'Username and password are required',
                ], JSON_PRETTY_PRINT));
                
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $result = $this->authService->login(
                $body['username'],
                $body['password']
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result,
            ], JSON_PRETTY_PRINT));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Internal server error',
                'message' => 'An error occurred while authenticating',
            ], JSON_PRETTY_PRINT));
            
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}

