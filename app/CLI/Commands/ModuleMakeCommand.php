<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

/**
 * Comando para criar um novo módulo
 * 
 * Gera toda a estrutura de um módulo:
 * - Module.php
 * - Routes.php
 * - config.php
 * - Diretórios: Controller, Service, Repository, Entity, Migrations
 */
class ModuleMakeCommand implements CommandInterface
{
    public function name(): string
    {
        return 'module:make';
    }

    public function description(): string
    {
        return 'Cria um novo módulo';
    }

    public function handle(array $args): int
    {
        if (empty($args[0])) {
            echo "Erro: Nome do módulo é obrigatório\n";
            echo "Uso: module:make {Nome}\n";
            return 1;
        }

        $moduleName = $args[0];
        $modulePath = __DIR__ . '/../../Modules/' . $moduleName;
        
        if (is_dir($modulePath)) {
            echo "Erro: Módulo '{$moduleName}' já existe\n";
            return 1;
        }

        $this->createDirectoryStructure($modulePath);
        $this->createModuleFile($modulePath, $moduleName);
        $this->createRoutesFile($modulePath);
        $this->createConfigFile($modulePath, $moduleName);
        $this->createControllerFile($modulePath, $moduleName);
        $this->createDirectories($modulePath);

        echo "Módulo '{$moduleName}' criado com sucesso!\n";
        echo "Caminho: {$modulePath}\n";
        
        return 0;
    }

    private function createDirectoryStructure(string $modulePath): void
    {
        $directories = [
            $modulePath,
            $modulePath . '/Controller',
            $modulePath . '/Service',
            $modulePath . '/Repository',
            $modulePath . '/Entity',
            $modulePath . '/Migrations',
            $modulePath . '/Migrations/core',
            $modulePath . '/Migrations/tenant',
            $modulePath . '/Migrations/unit',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function createModuleFile(string $modulePath, string $moduleName): void
    {
        $className = $this->toClassName($moduleName);
        $content = <<<PHP
<?php

namespace Metamorphose\Modules\\{$className};

use Metamorphose\Kernel\Module\ModuleInterface;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Módulo {$className}
 */
class Module implements ModuleInterface
{
    public function register(ContainerInterface \$container): void
    {
        // Registrar serviços do módulo aqui
    }

    public function boot(): void
    {
        // Executar inicializações após o registro
    }

    public function routes(App \$app): void
    {
        // Registrar rotas aqui
        // Exemplo: \$app->get('/{$moduleName}', Controller\\{$className}Controller::class . ':index');
    }
}

PHP;
        file_put_contents($modulePath . '/Module.php', $content);
    }

    private function createRoutesFile(string $modulePath): void
    {
        $moduleName = basename($modulePath);
        $className = $this->toClassName($moduleName);
        $content = <<<PHP
<?php

namespace Metamorphose\Modules\\{$className};

/**
 * Arquivo de rotas do módulo
 */
class Routes
{
    // Rotas podem ser definidas aqui se necessário
}

PHP;
        file_put_contents($modulePath . '/Routes.php', $content);
    }

    private function createConfigFile(string $modulePath, string $moduleName): void
    {
        $className = $this->toClassName($moduleName);
        $content = <<<PHP
<?php

return [
    'name' => '{$className} Module',
    'version' => '1.0.0',
    'enabled' => true,
];

PHP;
        file_put_contents($modulePath . '/config.php', $content);
    }

    private function createControllerFile(string $modulePath, string $moduleName): void
    {
        $className = $this->toClassName($moduleName);
        $content = <<<PHP
<?php

namespace Metamorphose\Modules\\{$className}\Controller;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller principal do módulo {$className}
 */
class {$className}Controller
{
    private RequestContext \$requestContext;
    private TenantContext \$tenantContext;
    private UnitContext \$unitContext;

    public function __construct(
        RequestContext \$requestContext,
        TenantContext \$tenantContext,
        UnitContext \$unitContext
    ) {
        \$this->requestContext = \$requestContext;
        \$this->tenantContext = \$tenantContext;
        \$this->unitContext = \$unitContext;
    }

    public function index(
        ServerRequestInterface \$request,
        ResponseInterface \$response
    ): ResponseInterface {
        \$data = [
            'message' => 'Hello from {$className} Module!',
            'request_id' => \$this->requestContext->getRequestId(),
        ];
        
        \$response->getBody()->write(json_encode(\$data, JSON_PRETTY_PRINT));
        return \$response->withHeader('Content-Type', 'application/json');
    }
}

PHP;
        file_put_contents($modulePath . '/Controller/' . $className . 'Controller.php', $content);
    }

    private function createDirectories(string $modulePath): void
    {
        $directories = [
            $modulePath . '/Service',
            $modulePath . '/Repository',
            $modulePath . '/Entity',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function toClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}

