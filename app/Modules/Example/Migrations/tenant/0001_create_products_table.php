<?php

use Doctrine\DBAL\Connection;

/**
 * Migration: Create products table
 */
class Migration0001CreateProductsTable
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function up(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $platformClass = get_class($platform);
        $isSqlite = strpos($platformClass, 'SQLite') !== false;
        
        if ($isSqlite) {
            $sql = "CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )";
        } else {
            $sql = "CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                created_at DATETIME,
                updated_at DATETIME,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        $this->connection->executeStatement($sql);
    }

    public function down(): void
    {
        $this->connection->executeStatement("DROP TABLE IF EXISTS products");
    }
}

