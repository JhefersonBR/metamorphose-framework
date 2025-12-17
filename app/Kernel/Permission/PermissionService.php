<?php

namespace Metamorphose\Kernel\Permission;

use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

/**
 * Serviço de permissões
 * 
 * Valida permissões com base no contexto atual (tenant, unit, global).
 */
class PermissionService
{
    private PermissionResolver $resolver;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        PermissionResolver $resolver,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->resolver = $resolver;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function hasPermission(string $permissionCode, ?string $userId = null): bool
    {
        $scope = $this->resolver->resolveScope($permissionCode);
        $contextId = $this->resolver->getContextId($scope);
        
        return $this->checkPermission($permissionCode, $scope, $contextId, $userId);
    }

    private function checkPermission(
        string $permissionCode,
        string $scope,
        ?string $contextId,
        ?string $userId
    ): bool {
        if ($scope === 'global') {
            return $this->checkGlobalPermission($permissionCode, $userId);
        }
        
        if ($scope === 'tenant' && $contextId === null) {
            return false;
        }
        
        if ($scope === 'tenant') {
            return $this->checkTenantPermission($permissionCode, $contextId, $userId);
        }
        
        if ($scope === 'unit' && $contextId === null) {
            return false;
        }
        
        if ($scope === 'unit') {
            return $this->checkUnitPermission($permissionCode, $contextId, $userId);
        }
        
        return false;
    }

    private function checkGlobalPermission(string $permissionCode, ?string $userId): bool
    {
        // Implementação deve ser feita pelo módulo de autenticação/autorização
        // Por enquanto, retorna false como padrão seguro
        return false;
    }

    private function checkTenantPermission(
        string $permissionCode,
        string $tenantId,
        ?string $userId
    ): bool {
        // Implementação deve ser feita pelo módulo de autenticação/autorização
        // Por enquanto, retorna false como padrão seguro
        return false;
    }

    private function checkUnitPermission(
        string $permissionCode,
        string $unitId,
        ?string $userId
    ): bool {
        // Implementação deve ser feita pelo módulo de autenticação/autorização
        // Por enquanto, retorna false como padrão seguro
        return false;
    }
}

