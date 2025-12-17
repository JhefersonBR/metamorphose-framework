<?php

namespace Metamorphose\Kernel\Log;

use Metamorphose\Kernel\Log\LogContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de log HTTP
 * 
 * Registra automaticamente informações sobre requisições HTTP:
 * - Método, URI, status code, tempo de resposta
 * - Contexto enriquecido (request_id, tenant_id, unit_id, user_id)
 */
class HttpLogMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private LogContext $logContext;
    private bool $enabled;

    public function __construct(
        LoggerInterface $logger,
        LogContext $logContext,
        bool $enabled = true
    ) {
        $this->logger = $logger;
        $this->logContext = $logContext;
        $this->enabled = $enabled;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $startTime = microtime(true);
        
        $response = $handler->handle($request);
        
        $duration = microtime(true) - $startTime;
        
        $context = $this->logContext->enrich([
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
            'ip' => $this->getClientIp($request),
        ]);
        
        $level = $this->determineLogLevel($response->getStatusCode());
        $this->logger->log($level, 'HTTP Request', $context);
        
        return $response;
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (isset($serverParams[$header])) {
                $ip = $serverParams[$header];
                if (is_string($ip) && $ip !== '') {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }

    private function determineLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }
        
        if ($statusCode >= 400) {
            return 'warning';
        }
        
        return 'info';
    }
}

