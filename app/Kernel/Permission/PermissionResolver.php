<?php

namespace Metamorphose\Kernel\Permission;

use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

/**
 * Resolvedor de permissões
 * 
 * Resolve permissões baseadas no escopo (global, tenant, unit)
 * usando os contextos de Tenant e Unit.
 */
class PermissionResolver
{
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function resolveScope(string $permissionCode): string
    {
        if (str_starts_with($permissionCode, 'global:')) {
            return 'global';
        }
        
        if (str_starts_with($permissionCode, 'tenant:')) {
            return 'tenant';
        }
        
        if (str_starts_with($permissionCode, 'unit:')) {
            return 'unit';
        }
        
        return 'global';
    }

    public function getContextId(string $scope): ?string
    {
        return match ($scope) {
            'tenant' => $this->tenantContext->getTenantId(),
            'unit' => $this->unitContext->getUnitId(),
            default => null,
        };
    }
}

