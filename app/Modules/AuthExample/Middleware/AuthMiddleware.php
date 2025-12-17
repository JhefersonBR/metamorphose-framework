<?php

namespace Metamorphose\Modules\AuthExample\Middleware;

use Metamorphose\Modules\AuthExample\Service\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware de autenticação JWT
 */
class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Obter token do header Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return $this->unauthorizedResponse('Authorization header is required');
        }

        // Extrair token (Bearer TOKEN)
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid authorization header format');
        }

        $token = $matches[1];

        // Validar token
        $user = $this->authService->validateToken($token);
        
        if (!$user) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        // Adicionar usuário ao request como atributo
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
        ], JSON_PRETTY_PRINT));
        
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}

