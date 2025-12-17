<?php

/**
 * Migration para criar tabela de usuários
 */
class Migration0001CreateUsersTable
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->connection->exec($sql);
        
        // Criar índice para busca rápida por username e email
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_username ON users(username)");
        $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_email ON users(email)");
    }

    public function down(): void
    {
        $this->connection->exec("DROP TABLE IF EXISTS users");
    }
}

