<?php

/**
 * Exemplo de configuração de banco de dados
 * 
 * Copie este arquivo para config/database.php e ajuste conforme necessário
 * 
 * Drivers suportados:
 * - sqlite: SQLite (desenvolvimento/testes)
 * - mysql, mariadb: MySQL/MariaDB
 * - pgsql, postgresql, postgres: PostgreSQL
 * - sqlsrv, mssql, sqlserver: SQL Server
 * - oracle, oci: Oracle
 */

return [
    'core' => [
        // SQLite (padrão para desenvolvimento)
        'driver' => getenv('DB_CORE_DRIVER') ?: 'sqlite',
        'database' => getenv('DB_CORE_DATABASE') ?: __DIR__ . '/../storage/database.sqlite',
        'username' => '',
        'password' => '',
        'charset' => 'utf8',
        
        // MySQL/MariaDB
        // 'driver' => 'mysql',
        // 'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        // 'port' => getenv('DB_CORE_PORT') ?: 3306,
        // 'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
        // 'username' => getenv('DB_CORE_USERNAME') ?: 'root',
        // 'password' => getenv('DB_CORE_PASSWORD') ?: '',
        // 'charset' => 'utf8mb4',
        // 'collation' => 'utf8mb4_unicode_ci',
        
        // PostgreSQL
        // 'driver' => 'pgsql',
        // 'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        // 'port' => getenv('DB_CORE_PORT') ?: 5432,
        // 'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
        // 'username' => getenv('DB_CORE_USERNAME') ?: 'postgres',
        // 'password' => getenv('DB_CORE_PASSWORD') ?: '',
        // 'charset' => 'UTF8',
        
        // SQL Server
        // 'driver' => 'sqlsrv',
        // 'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        // 'port' => getenv('DB_CORE_PORT') ?: 1433,
        // 'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
        // 'username' => getenv('DB_CORE_USERNAME') ?: 'sa',
        // 'password' => getenv('DB_CORE_PASSWORD') ?: '',
        // 'charset' => 'UTF-8',
        
        // Oracle
        // 'driver' => 'oracle',
        // 'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        // 'port' => getenv('DB_CORE_PORT') ?: 1521,
        // 'database' => getenv('DB_CORE_DATABASE') ?: 'XE', // SID ou Service Name
        // 'username' => getenv('DB_CORE_USERNAME') ?: 'system',
        // 'password' => getenv('DB_CORE_PASSWORD') ?: '',
        // 'charset' => 'AL32UTF8',
    ],
    
    'tenant' => [
        'driver' => getenv('DB_TENANT_DRIVER') ?: 'sqlite',
        'database' => getenv('DB_TENANT_DATABASE') ?: __DIR__ . '/../storage/tenant_database.sqlite',
        'username' => '',
        'password' => '',
        'charset' => 'utf8',
    ],
    
    'unit' => [
        'driver' => getenv('DB_UNIT_DRIVER') ?: 'sqlite',
        'database' => getenv('DB_UNIT_DATABASE') ?: __DIR__ . '/../storage/unit_database.sqlite',
        'username' => '',
        'password' => '',
        'charset' => 'utf8',
    ],
];

