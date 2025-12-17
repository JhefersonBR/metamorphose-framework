<?php

namespace Metamorphose\Modules\AuthExample\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Metamorphose\Modules\AuthExample\Entity\User;
use Metamorphose\Modules\AuthExample\Repository\UserRepository;

/**
 * Serviço de autenticação JWT
 */
class AuthService
{
    private UserRepository $userRepository;
    private string $jwtSecret;
    private string $jwtAlgorithm = 'HS256';
    private int $jwtExpiration = 3600; // 1 hora

    public function __construct(UserRepository $userRepository, string $jwtSecret)
    {
        $this->userRepository = $userRepository;
        $this->jwtSecret = $jwtSecret;
    }

    public function register(string $username, string $email, string $password): array
    {
        // Validar dados
        if (empty($username) || empty($email) || empty($password)) {
            throw new \InvalidArgumentException('Username, email and password are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }

        // Verificar se usuário já existe
        if ($this->userRepository->usernameExists($username)) {
            throw new \RuntimeException('Username already exists');
        }

        if ($this->userRepository->emailExists($email)) {
            throw new \RuntimeException('Email already exists');
        }

        // Criar usuário
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = new User($username, $email, $hashedPassword);
        $user = $this->userRepository->create($user);

        // Gerar token JWT
        $token = $this->generateToken($user);

        return [
            'user' => $user->toArray(),
            'token' => $token,
        ];
    }

    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Username and password are required');
        }

        // Buscar usuário
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            throw new \RuntimeException('Invalid credentials');
        }

        // Verificar senha
        if (!password_verify($password, $user->getPassword())) {
            throw new \RuntimeException('Invalid credentials');
        }

        // Gerar token JWT
        $token = $this->generateToken($user);

        return [
            'user' => $user->toArray(),
            'token' => $token,
        ];
    }

    public function validateToken(string $token): ?User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            
            $userId = $decoded->user_id ?? null;
            if (!$userId) {
                return null;
            }

            return $this->userRepository->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'iss' => 'metamorphose-framework',
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration,
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }
}

