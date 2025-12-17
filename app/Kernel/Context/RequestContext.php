<?php

namespace Metamorphose\Kernel\Context;

/**
 * Contexto de Request
 * 
 * Armazena informações da requisição HTTP atual.
 * Gera um request_id único por requisição.
 */
class RequestContext
{
    private string $requestId;
    private ?string $userId = null;
    private array $requestData = [];

    public function __construct()
    {
        $this->requestId = $this->generateRequestId();
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setRequestData(array $data): void
    {
        $this->requestData = $data;
    }

    public function getRequestData(): array
    {
        return $this->requestData;
    }

    public function clear(): void
    {
        $this->requestId = $this->generateRequestId();
        $this->userId = null;
        $this->requestData = [];
    }
}

