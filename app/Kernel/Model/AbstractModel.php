<?php

namespace Metamorphose\Kernel\Model;

use Doctrine\DBAL\Connection;
use Metamorphose\Kernel\Context\TenantContext;
use Metamorphose\Kernel\Context\UnitContext;
use Metamorphose\Kernel\Database\DBALConnectionResolver;
use Metamorphose\Kernel\Database\Query\QueryCriteria;
use Metamorphose\Kernel\Database\Query\QueryFilter;

/**
 * Model abstrato estilo Adianti Framework
 * 
 * Abstração sobre Doctrine DBAL (NÃO usa Doctrine ORM nem EntityManager)
 * Table-first, sem annotations ou attributes
 */
abstract class AbstractModel
{
    /**
     * Nome da tabela (obrigatório)
     */
    protected static string $table;

    /**
     * Chave primária (padrão: 'id')
     */
    protected static string $primaryKey = 'id';

    /**
     * Escopo da conexão: 'core', 'tenant' ou 'unit' (padrão: 'tenant')
     */
    protected static string $scope = 'tenant';

    /**
     * Dados do model
     */
    protected array $data = [];

    /**
     * Resolvedor de conexões (injetado estaticamente)
     */
    private static ?DBALConnectionResolver $connectionResolver = null;

    /**
     * Contextos (injetados estaticamente)
     */
    private static ?TenantContext $tenantContext = null;
    private static ?UnitContext $unitContext = null;

    /**
     * Define o resolvedor de conexões (deve ser chamado no bootstrap)
     */
    public static function setConnectionResolver(DBALConnectionResolver $resolver): void
    {
        self::$connectionResolver = $resolver;
    }

    /**
     * Define os contextos (deve ser chamado no bootstrap)
     */
    public static function setContexts(
        TenantContext $tenantContext,
        UnitContext $unitContext
    ): void {
        self::$tenantContext = $tenantContext;
        self::$unitContext = $unitContext;
    }

    /**
     * Carrega um model ou lista de models
     * 
     * @param int|QueryCriteria $criteria ID ou critério de busca
     * @return static|array|null Model único, array de models ou null
     */
    public static function load(int|QueryCriteria $criteria): static|array|null
    {
        if (is_int($criteria)) {
            return self::loadById($criteria);
        }

        return self::loadByCriteria($criteria);
    }

    /**
     * Carrega model por ID
     */
    private static function loadById(int $id): ?static
    {
        $connection = self::getConnection();
        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $sql = "SELECT * FROM `{$table}` WHERE `{$primaryKey}` = :id LIMIT 1";
        $result = $connection->fetchAssociative($sql, ['id' => $id]);

        if ($result === false) {
            return null;
        }

        $model = new static();
        $model->fromArray($result);
        return $model;
    }

    /**
     * Carrega models por critério
     * 
     * @return static[]
     */
    private static function loadByCriteria(QueryCriteria $criteria): array
    {
        $connection = self::getConnection();
        $table = static::$table;

        $whereSQL = $criteria->toSQL();
        $orderBySQL = $criteria->toOrderBySQL();
        $groupBySQL = $criteria->toGroupBySQL();
        $limitSQL = $criteria->getLimit() !== null ? 'LIMIT ' . $criteria->getLimit() : '';
        $offsetSQL = $criteria->getOffset() !== null ? 'OFFSET ' . $criteria->getOffset() : '';

        $sql = "SELECT * FROM `{$table}`";
        if ($whereSQL['sql']) {
            $sql .= ' ' . $whereSQL['sql'];
        }
        if ($groupBySQL) {
            $sql .= ' ' . $groupBySQL;
        }
        if ($orderBySQL) {
            $sql .= ' ' . $orderBySQL;
        }
        if ($limitSQL) {
            $sql .= ' ' . $limitSQL;
        }
        if ($offsetSQL) {
            $sql .= ' ' . $offsetSQL;
        }

        $results = $connection->fetchAllAssociative($sql, $whereSQL['params']);

        $models = [];
        foreach ($results as $row) {
            $model = new static();
            $model->fromArray($row);
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Salva o model (insert ou update)
     */
    public function store(): void
    {
        $connection = self::getConnection();
        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $data = $this->toArray();

        // Remove a chave primária se for null ou 0 (novo registro)
        $id = $data[$primaryKey] ?? null;
        if ($id === null || $id === 0 || $id === '') {
            unset($data[$primaryKey]);
        }

        // Remove campos null
        $data = array_filter($data, fn($value) => $value !== null);

        if (isset($id) && $id !== 0 && $id !== '') {
            // Update
            $connection->update($table, $data, [$primaryKey => $id]);
            $this->data[$primaryKey] = $id;
        } else {
            // Insert
            $connection->insert($table, $data);
            $lastInsertId = $connection->lastInsertId();
            if ($lastInsertId) {
                $this->data[$primaryKey] = (int) $lastInsertId;
            }
        }
    }

    /**
     * Deleta o model
     * 
     * @param int|null $id ID a deletar (se null, usa o ID do model atual)
     */
    public function delete(?int $id = null): void
    {
        $connection = self::getConnection();
        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $idToDelete = $id ?? ($this->data[$primaryKey] ?? null);

        if ($idToDelete === null) {
            throw new \RuntimeException('Cannot delete: ID not specified');
        }

        $connection->delete($table, [$primaryKey => $idToDelete]);

        if ($id === null || $id === $this->data[$primaryKey]) {
            $this->data = [];
        }
    }

    /**
     * Converte model para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Carrega dados de um array
     * 
     * @param array $data Dados
     */
    public function fromArray(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Obtém um valor do model
     * 
     * @param string $key Chave
     * @param mixed $default Valor padrão
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Define um valor no model
     * 
     * @param string $key Chave
     * @param mixed $value Valor
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Verifica se uma chave existe
     * 
     * @param string $key Chave
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Obtém a conexão DBAL baseada no escopo
     */
    protected static function getConnection(): Connection
    {
        if (self::$connectionResolver === null) {
            throw new \RuntimeException('ConnectionResolver not set. Call AbstractModel::setConnectionResolver() in bootstrap.');
        }

        // Para models, não permitimos conexão padrão - precisa ter tenant/unit no contexto
        return self::$connectionResolver->connection(static::$scope, false);
    }

    /**
     * Obtém o nome da tabela
     */
    public static function getTable(): string
    {
        return static::$table;
    }

    /**
     * Obtém a chave primária
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Obtém o escopo
     */
    public static function getScope(): string
    {
        return static::$scope;
    }
}

