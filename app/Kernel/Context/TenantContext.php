<?php

namespace Metamorphose\Kernel\Context;

/**
 * Contexto de Tenant
 * 
 * Armazena informações do tenant atual da requisição.
 * Não mantém estado global persistente - é preenchido via middleware.
 */
class TenantContext
{
    private ?string $tenantId = null;
    private ?string $tenantCode = null;
    private array $tenantData = [];

    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantCode(?string $tenantCode): void
    {
        $this->tenantCode = $tenantCode;
    }

    public function getTenantCode(): ?string
    {
        return $this->tenantCode;
    }

    public function setTenantData(array $data): void
    {
        $this->tenantData = $data;
    }

    public function getTenantData(): array
    {
        return $this->tenantData;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->tenantCode = null;
        $this->tenantData = [];
    }
}

