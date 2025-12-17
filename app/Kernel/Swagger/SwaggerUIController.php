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
        $swaggerJsonUrl = '/swagger.json';
        
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
                layout: "StandaloneLayout"
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
        $response->getBody()->write($json);
        return $response->withHeader('Content-Type', 'application/json');
    }
}

