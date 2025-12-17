<?php

namespace Metamorphose\Kernel\Database;

use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use PDO;

/**
 * Resolvedor de conexões de banco de dados
 * 
 * Resolve conexões baseadas no escopo (core, tenant, unit)
 * usando os contextos de Tenant e Unit.
 */
class ConnectionResolver implements ConnectionResolverInterface
{
    private array $config;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;
    private array $connections = [];

    public function __construct(
        array $config,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->config = $config;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function resolveCore(): PDO
    {
        $key = 'core';
        
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = $this->createConnection($this->config['core']);
        }
        
        return $this->connections[$key];
    }

    public function resolveTenant(?string $tenantId = null): PDO
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();
        
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant ID não disponível no contexto');
        }
        
        $key = "tenant_{$tenantId}";
        
        if (!isset($this->connections[$key])) {
            $config = $this->config['tenant'];
            $this->connections[$key] = $this->createConnection($config);
        }
        
        return $this->connections[$key];
    }

    public function resolveUnit(?string $unitId = null): PDO
    {
        $unitId = $unitId ?? $this->unitContext->getUnitId();
        
        if ($unitId === null) {
            throw new \RuntimeException('Unit ID não disponível no contexto');
        }
        
        $key = "unit_{$unitId}";
        
        if (!isset($this->connections[$key])) {
            $config = $this->config['unit'];
            $this->connections[$key] = $this->createConnection($config);
        }
        
        return $this->connections[$key];
    }

    private function createConnection(array $config): PDO
    {
        // Suporte para SQLite
        if ($config['driver'] === 'sqlite') {
            $dsn = 'sqlite:' . $config['database'];
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            return new PDO($dsn, null, null, $options);
        }
        
        // MySQL/MariaDB
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
}

