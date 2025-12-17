<?php

namespace Metamorphose\Kernel\Log;

use Metamorphose\Kernel\Context\RequestContext;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;

/**
 * Contexto de Log
 * 
 * Adiciona automaticamente informações de contexto aos logs:
 * - request_id
 * - tenant_id
 * - unit_id
 * - user_id (quando existir)
 */
class LogContext
{
    private RequestContext $requestContext;
    private TenantContext $tenantContext;
    private UnitContext $unitContext;

    public function __construct(
        RequestContext $requestContext,
        TenantContext $tenantContext,
        UnitContext $unitContext
    ) {
        $this->requestContext = $requestContext;
        $this->tenantContext = $tenantContext;
        $this->unitContext = $unitContext;
    }

    public function enrich(array $context): array
    {
        $enriched = $context;
        
        $enriched['request_id'] = $this->requestContext->getRequestId();
        
        if ($this->tenantContext->hasTenant()) {
            $enriched['tenant_id'] = $this->tenantContext->getTenantId();
        }
        
        if ($this->unitContext->hasUnit()) {
            $enriched['unit_id'] = $this->unitContext->getUnitId();
        }
        
        if ($this->requestContext->getUserId() !== null) {
            $enriched['user_id'] = $this->requestContext->getUserId();
        }
        
        return $enriched;
    }
}

