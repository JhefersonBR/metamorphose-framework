<?php

namespace Metamorphose\Kernel\Swagger;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller para servir Swagger UI
 */
class SwaggerUIController
{
    private string $swaggerJsonPath;
    private string $swaggerUiPath;

    public function __construct(string $swaggerJsonPath, string $swaggerUiPath)
    {
        $this->swaggerJsonPath = $swaggerJsonPath;
        $this->swaggerUiPath = $swaggerUiPath;
    }

    public function ui(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme() ?: 'http';
        $host = $uri->getHost() ?: 'localhost';
        $port = $uri->getPort();
        
        // Construir URL base completa
        $baseUrl = $scheme . '://' . $host;
        if ($port !== null && $port !== 80 && $port !== 443) {
            $baseUrl .= ':' . $port;
        }
        
        $swaggerJsonUrl = $baseUrl . '/swagger.json';
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI - Metamorphose Framework</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const baseUrl = "{$baseUrl}";
            
            const ui = SwaggerUIBundle({
                url: "{$swaggerJsonUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                requestInterceptor: function(request) {
                    // Garantir que todas as requisições usem a URL completa com porta
                    if (request.url) {
                        // Se a URL é relativa (começa com /)
                        if (request.url.startsWith('/')) {
                            request.url = baseUrl + request.url;
                        }
                        // Se a URL contém http://localhost (com ou sem porta)
                        else if (request.url.indexOf('http://localhost') === 0) {
                            // Substituir http://localhost (com qualquer porta ou sem porta) pela URL base correta
                            const urlMatch = request.url.match(/^http:\/\/localhost(?::\d+)?(\/.*)?$/);
                            if (urlMatch) {
                                const path = urlMatch[1] || '';
                                request.url = baseUrl + path;
                            }
                        }
                    }
                    
                    return request;
                },
                onComplete: function() {
                    // Forçar atualização das URLs dos servidores após carregar
                    if (ui && ui.specSelectors && ui.specSelectors.specJson) {
                        const spec = ui.specSelectors.specJson();
                        if (spec && spec.get && spec.get('servers')) {
                            const servers = spec.get('servers').toJS();
                            if (servers && servers.length > 0) {
                                servers.forEach(function(server, index) {
                                    if (server.url && server.url.includes('http://localhost')) {
                                        servers[index].url = baseUrl;
                                    }
                                });
                            }
                        }
                    }
                }
            });
        };
    </script>
</body>
</html>
HTML;

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function json(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!file_exists($this->swaggerJsonPath)) {
            $response->getBody()->write(json_encode([
                'error' => 'Documentação Swagger não encontrada. Execute: php bin/metamorphose swagger:generate'
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $json = file_get_contents($this->swaggerJsonPath);
        $swaggerData = json_decode($json, true);
        
        // Atualizar URL do servidor baseado na requisição atual
        if (isset($swaggerData['servers']) && is_array($swaggerData['servers'])) {
            $uri = $request->getUri();
            $scheme = $uri->getScheme() ?: 'http';
            $host = $uri->getHost() ?: 'localhost';
            $port = $uri->getPort();
            
            // Construir URL base
            $baseUrl = $scheme . '://' . $host;
            if ($port !== null && $port !== 80 && $port !== 443) {
                $baseUrl .= ':' . $port;
            }
            
            // Atualizar todas as URLs de servidor
            foreach ($swaggerData['servers'] as &$server) {
                $server['url'] = $baseUrl;
            }
        }
        
        $response->getBody()->write(json_encode($swaggerData, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

