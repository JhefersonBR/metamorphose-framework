<?php

namespace Metamorphose\Modules\AuthExample\Repository;

use Metamorphose\Kernel\Database\ConnectionResolverInterface;
use Metamorphose\Modules\AuthExample\Entity\User;
use PDO;

/**
 * Repository para gerenciar usuários
 */
class UserRepository
{
    private ConnectionResolverInterface $connectionResolver;

    public function __construct(ConnectionResolverInterface $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
    }

    public function create(User $user): User
    {
        $connection = $this->connectionResolver->resolveCore();
        
        $sql = "INSERT INTO users (username, email, password, created_at, updated_at) 
                VALUES (:username, :email, :password, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            ':username' => $user->getUsername(),
            ':email' => $user->getEmail(),
            ':password' => $user->getPassword(),
        ]);
        
        $user = new User(
            $user->getUsername(),
            $user->getEmail(),
            $user->getPassword(),
            (int) $connection->lastInsertId(),
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );
        
        return $user;
    }

    public function findByUsername(string $username): ?User
    {
        $connection = $this->connectionResolver->resolveCore();
        
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':username' => $username]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return User::fromArray($data);
    }

    public function findByEmail(string $email): ?User
    {
        $connection = $this->connectionResolver->resolveCore();
        
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return User::fromArray($data);
    }

    public function findById(int $id): ?User
    {
        $connection = $this->connectionResolver->resolveCore();
        
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return User::fromArray($data);
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}

