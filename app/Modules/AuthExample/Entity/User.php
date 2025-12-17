<?php

namespace Metamorphose\Modules\AuthExample\Entity;

/**
 * Entidade User
 */
class User
{
    private ?int $id = null;
    private string $username;
    private string $email;
    private string $password;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(
        string $username,
        string $email,
        string $password,
        ?int $id = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['id'] ?? null,
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }
}

