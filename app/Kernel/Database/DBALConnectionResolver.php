<?php

namespace Metamorphose\Kernel\Database;

use Doctrine\DBAL\Connection;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

/**
 * Resolvedor de conexões DBAL
 * 
 * Resolve conexões Doctrine DBAL baseadas no escopo (core, tenant, unit)
 * usando os contextos de Tenant e Unit.
 */
class DBALConnectionResolver
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

    /**
     * Resolve conexão core
     * 
     * @return Connection Conexão DBAL para escopo core
     */
    public function resolveCore(): Connection
    {
        $key = 'core';
        
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = DBALConnectionFactory::create($this->config['core']);
        }
        
        return $this->connections[$key];
    }

    /**
     * Resolve conexão tenant
     * 
     * @param string|null $tenantId ID do tenant (opcional, usa contexto se não fornecido)
     * @param bool $allowDefault Se true, permite usar conexão padrão quando não há tenant ID
     * @return Connection Conexão DBAL para escopo tenant
     */
    public function resolveTenant(?string $tenantId = null, bool $allowDefault = false): Connection
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();
        
        // Se não há tenant ID e allowDefault é true, usa conexão padrão (para migrations)
        if ($tenantId === null) {
            if ($allowDefault) {
                $key = 'tenant_default';
                if (!isset($this->connections[$key])) {
                    $config = $this->config['tenant'];
                    $this->connections[$key] = DBALConnectionFactory::create($config);
                }
                return $this->connections[$key];
            }
            throw new \RuntimeException('Tenant ID not available in context');
        }
        
        $key = "tenant_{$tenantId}";
        
        if (!isset($this->connections[$key])) {
            $config = $this->config['tenant'];
            $this->connections[$key] = DBALConnectionFactory::create($config);
        }
        
        return $this->connections[$key];
    }

    /**
     * Resolve conexão unit
     * 
     * @param string|null $unitId ID da unit (opcional, usa contexto se não fornecido)
     * @param bool $allowDefault Se true, permite usar conexão padrão quando não há unit ID
     * @return Connection Conexão DBAL para escopo unit
     */
    public function resolveUnit(?string $unitId = null, bool $allowDefault = false): Connection
    {
        $unitId = $unitId ?? $this->unitContext->getUnitId();
        
        // Se não há unit ID e allowDefault é true, usa conexão padrão (para migrations)
        if ($unitId === null) {
            if ($allowDefault) {
                $key = 'unit_default';
                if (!isset($this->connections[$key])) {
                    $config = $this->config['unit'];
                    $this->connections[$key] = DBALConnectionFactory::create($config);
                }
                return $this->connections[$key];
            }
            throw new \RuntimeException('Unit ID not available in context');
        }
        
        $key = "unit_{$unitId}";
        
        if (!isset($this->connections[$key])) {
            $config = $this->config['unit'];
            $this->connections[$key] = DBALConnectionFactory::create($config);
        }
        
        return $this->connections[$key];
    }

    /**
     * Resolve conexão por escopo
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @param bool $allowDefault Se true, permite conexão padrão para tenant/unit sem ID
     * @return Connection Conexão DBAL
     */
    public function connection(string $scope, bool $allowDefault = false): Connection
    {
        return match ($scope) {
            'core' => $this->resolveCore(),
            'tenant' => $this->resolveTenant(null, $allowDefault),
            'unit' => $this->resolveUnit(null, $allowDefault),
            default => throw new \InvalidArgumentException("Invalid scope: {$scope}"),
        };
    }
}

