<?php

namespace Metamorphose\Kernel\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Factory para criar conexões Doctrine DBAL
 * 
 * Converte configurações do framework para formato DBAL
 */
class DBALConnectionFactory
{
    /**
     * Cria uma conexão DBAL a partir da configuração
     * 
     * @param array $config Configuração do banco de dados
     * @return Connection Conexão DBAL
     */
    public static function create(array $config): Connection
    {
        $params = self::convertConfig($config);
        
        return DriverManager::getConnection($params);
    }

    /**
     * Converte configuração do framework para formato DBAL
     * 
     * @param array $config Configuração do framework
     * @return array Parâmetros DBAL
     */
    private static function convertConfig(array $config): array
    {
        $driver = strtolower($config['driver']);
        
        // Mapear drivers do framework para drivers DBAL
        $driverMap = [
            'mysql' => 'pdo_mysql',
            'mariadb' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'postgresql' => 'pdo_pgsql',
            'postgres' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
            'mssql' => 'pdo_sqlsrv',
            'sqlserver' => 'pdo_sqlsrv',
            'oracle' => 'oci8',
            'oci' => 'oci8',
        ];
        
        $dbalDriver = $driverMap[$driver] ?? $driver;
        
        $params = [
            'driver' => $dbalDriver,
        ];
        
        // SQLite tem formato especial
        if ($driver === 'sqlite') {
            $params['path'] = $config['database'];
            return $params;
        }
        
        // Oracle tem formato especial
        if ($driver === 'oracle' || $driver === 'oci') {
            // Oracle pode usar SID ou Service Name
            // Formato: host:port/sid ou host:port/service_name
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? 1521;
            $database = $config['database'] ?? 'XE';
            
            // Se database contém /, assume formato host:port/service
            if (strpos($database, '/') !== false) {
                $params['host'] = $host;
                $params['port'] = $port;
                $params['servicename'] = $database;
            } else {
                // Usa SID
                $params['host'] = $host;
                $params['port'] = $port;
                $params['dbname'] = $database;
            }
            
            $params['user'] = $config['username'] ?? 'system';
            $params['password'] = $config['password'] ?? '';
            $params['charset'] = $config['charset'] ?? 'AL32UTF8';
            
            return $params;
        }
        
        // SQL Server tem formato especial
        if ($driver === 'sqlsrv' || $driver === 'mssql' || $driver === 'sqlserver') {
            $params['host'] = $config['host'] ?? 'localhost';
            $params['port'] = $config['port'] ?? 1433;
            $params['dbname'] = $config['database'];
            $params['user'] = $config['username'] ?? 'sa';
            $params['password'] = $config['password'] ?? '';
            
            // SQL Server não usa charset da mesma forma
            if (isset($config['charset'])) {
                $params['charset'] = $config['charset'];
            }
            
            return $params;
        }
        
        // PostgreSQL, MySQL/MariaDB e outros usam formato padrão
        $params['host'] = $config['host'] ?? 'localhost';
        $params['port'] = $config['port'] ?? self::getDefaultPort($driver);
        $params['dbname'] = $config['database'];
        $params['user'] = $config['username'] ?? 'root';
        $params['password'] = $config['password'] ?? '';
        
        // Charset padrão por banco
        if (!isset($config['charset'])) {
            $params['charset'] = self::getDefaultCharset($driver);
        } else {
            $params['charset'] = $config['charset'];
        }
        
        // Opções adicionais para PDO
        $params['driverOptions'] = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return $params;
    }
    
    /**
     * Retorna porta padrão para o driver
     */
    private static function getDefaultPort(string $driver): int
    {
        return match (strtolower($driver)) {
            'mysql', 'mariadb' => 3306,
            'pgsql', 'postgresql', 'postgres' => 5432,
            'sqlsrv', 'mssql', 'sqlserver' => 1433,
            'oracle', 'oci' => 1521,
            default => 3306,
        };
    }
    
    /**
     * Retorna charset padrão para o driver
     */
    private static function getDefaultCharset(string $driver): string
    {
        return match (strtolower($driver)) {
            'mysql', 'mariadb' => 'utf8mb4',
            'pgsql', 'postgresql', 'postgres' => 'UTF8',
            'sqlsrv', 'mssql', 'sqlserver' => 'UTF-8',
            'oracle', 'oci' => 'AL32UTF8',
            default => 'utf8mb4',
        };
    }
}

