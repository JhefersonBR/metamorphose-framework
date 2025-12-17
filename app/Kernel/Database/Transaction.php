<?php

namespace Metamorphose\Kernel\Database;

use Doctrine\DBAL\Connection;

/**
 * Gerenciador de transações de banco de dados
 * 
 * A transação só faz commit quando é fechada (close()).
 * Suporta transações aninhadas.
 */
class Transaction
{
    private static ?DBALConnectionResolver $connectionResolver = null;
    
    /**
     * Pilha de transações por escopo
     * @var array<string, array{connection: Connection, level: int}>
     */
    private static array $transactions = [];

    /**
     * Define o resolvedor de conexões
     */
    public static function setConnectionResolver(DBALConnectionResolver $resolver): void
    {
        self::$connectionResolver = $resolver;
    }

    /**
     * Abre uma transação para o escopo especificado
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return Connection Conexão DBAL
     * @throws \RuntimeException Se o connection resolver não estiver configurado
     */
    public static function open(string $scope = 'core'): Connection
    {
        if (self::$connectionResolver === null) {
            throw new \RuntimeException('Connection resolver not configured. Call Transaction::setConnectionResolver() first.');
        }

        // Para escopos tenant/unit, permitir conexão padrão quando não há ID no contexto (desenvolvimento/exemplo)
        $allowDefault = in_array($scope, ['tenant', 'unit']);
        $connection = self::$connectionResolver->connection($scope, $allowDefault);

        // Se já existe transação para este escopo, incrementa o nível (transação aninhada)
        if (isset(self::$transactions[$scope])) {
            self::$transactions[$scope]['level']++;
            return $connection;
        }

        // Inicia nova transação
        $connection->beginTransaction();
        self::$transactions[$scope] = [
            'connection' => $connection,
            'level' => 1
        ];

        return $connection;
    }

    /**
     * Fecha a transação e faz commit automaticamente
     * 
     * Se houver transações aninhadas, apenas decrementa o nível.
     * O commit só acontece quando todas as transações aninhadas forem fechadas.
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return void
     * @throws \RuntimeException Se não houver transação aberta para o escopo
     */
    public static function close(string $scope = 'core'): void
    {
        if (!isset(self::$transactions[$scope])) {
            throw new \RuntimeException("No active transaction for scope: {$scope}. Call Transaction::open() first.");
        }

        $transaction = self::$transactions[$scope];
        $connection = $transaction['connection'];
        $level = $transaction['level'];

        // Se há transações aninhadas, apenas decrementa o nível
        if ($level > 1) {
            self::$transactions[$scope]['level']--;
            return;
        }

        // Última transação: faz commit e remove da pilha
        try {
            $connection->commit();
        } catch (\Exception $e) {
            // Em caso de erro no commit, faz rollback
            $connection->rollBack();
            unset(self::$transactions[$scope]);
            throw $e;
        }

        unset(self::$transactions[$scope]);
    }

    /**
     * Faz rollback da transação
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return void
     * @throws \RuntimeException Se não houver transação aberta para o escopo
     */
    public static function rollback(string $scope = 'core'): void
    {
        if (!isset(self::$transactions[$scope])) {
            throw new \RuntimeException("No active transaction for scope: {$scope}. Call Transaction::open() first.");
        }

        $connection = self::$transactions[$scope]['connection'];
        $connection->rollBack();
        unset(self::$transactions[$scope]);
    }

    /**
     * Verifica se há transação ativa para o escopo
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return bool
     */
    public static function active(string $scope = 'core'): bool
    {
        return isset(self::$transactions[$scope]);
    }

    /**
     * Obtém a conexão da transação ativa
     * 
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return Connection|null Conexão DBAL ou null se não houver transação ativa
     */
    public static function getConnection(string $scope = 'core'): ?Connection
    {
        return self::$transactions[$scope]['connection'] ?? null;
    }

    /**
     * Executa um callback dentro de uma transação
     * 
     * Abre a transação, executa o callback e fecha (fazendo commit).
     * Se houver exceção, faz rollback automaticamente.
     * 
     * @param callable $callback Callback a ser executado
     * @param string $scope Escopo: 'core', 'tenant' ou 'unit'
     * @return mixed Retorno do callback
     * @throws \Exception Qualquer exceção lançada pelo callback
     */
    public static function run(callable $callback, string $scope = 'core'): mixed
    {
        self::open($scope);
        
        try {
            $result = $callback(self::getConnection($scope));
            self::close($scope);
            return $result;
        } catch (\Exception $e) {
            self::rollback($scope);
            throw $e;
        }
    }
}

