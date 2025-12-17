<?php

namespace Metamorphose\Kernel\Context;

/**
 * Contexto de Unit
 * 
 * Armazena informações da unidade atual da requisição.
 * Não mantém estado global persistente - é preenchido via middleware.
 */
class UnitContext
{
    private ?string $unitId = null;
    private ?string $unitCode = null;
    private array $unitData = [];

    public function setUnitId(?string $unitId): void
    {
        $this->unitId = $unitId;
    }

    public function getUnitId(): ?string
    {
        return $this->unitId;
    }

    public function setUnitCode(?string $unitCode): void
    {
        $this->unitCode = $unitCode;
    }

    public function getUnitCode(): ?string
    {
        return $this->unitCode;
    }

    public function setUnitData(array $data): void
    {
        $this->unitData = $data;
    }

    public function getUnitData(): array
    {
        return $this->unitData;
    }

    public function hasUnit(): bool
    {
        return $this->unitId !== null;
    }

    public function clear(): void
    {
        $this->unitId = null;
        $this->unitCode = null;
        $this->unitData = [];
    }
}

