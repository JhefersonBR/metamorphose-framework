<?php

namespace Metamorphose\Kernel\Swagger;

use OpenApi\Attributes as OA;

/**
 * Informações da API OpenAPI
 * 
 * Este arquivo define as informações gerais da API.
 * Coloque este arquivo na raiz do diretório que será escaneado.
 */
#[OA\Info(
    version: "1.0.0",
    description: "Metamorphose Framework API Documentation",
    title: "Metamorphose Framework API"
)]
#[OA\Server(
    url: "http://localhost",
    description: "Servidor de desenvolvimento"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class OpenApiInfo
{
}

