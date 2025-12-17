<?php

/**
 * Migração de exemplo para escopo core
 * 
 * Esta migração demonstra como criar uma tabela no escopo core.
 */
class CreateExampleTable
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS examples (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->connection->exec($sql);
    }
}

