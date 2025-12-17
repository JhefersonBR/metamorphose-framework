<?php

namespace Metamorphose\Kernel\Database\Query;

/**
 * Agrupa filtros e configurações de consulta
 * 
 * Suporta AND/OR, orderBy, limit, offset e groupBy com API fluente
 */
class QueryCriteria
{
    private array $filters = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $groupBy = [];
    private string $defaultLogicalOperator = 'AND';

    /**
     * Adiciona um filtro
     * 
     * @param QueryFilter $filter Filtro a ser adicionado
     * @return self
     */
    public function add(QueryFilter $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Adiciona um filtro simples (método helper)
     * 
     * @param string $field Campo
     * @param string $operator Operador
     * @param mixed $value Valor
     * @param string|null $logicalOperator Operador lógico (AND/OR)
     * @return self
     */
    public function addFilter(
        string $field,
        string $operator,
        mixed $value = null,
        ?string $logicalOperator = null
    ): self {
        return $this->add(new QueryFilter($field, $operator, $value, $logicalOperator));
    }

    /**
     * Define ordenação
     * 
     * @param string $field Campo
     * @param string $direction ASC ou DESC
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'field' => $field,
            'direction' => strtoupper($direction),
        ];
        return $this;
    }

    /**
     * Define limite
     * 
     * @param int $limit Limite de registros
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Define offset
     * 
     * @param int $offset Offset de registros
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Define agrupamento
     * 
     * @param string $field Campo para agrupar
     * @return self
     */
    public function groupBy(string $field): self
    {
        $this->groupBy[] = $field;
        return $this;
    }

    /**
     * Define operador lógico padrão para novos filtros
     * 
     * @param string $operator AND ou OR
     * @return self
     */
    public function setDefaultLogicalOperator(string $operator): self
    {
        $this->defaultLogicalOperator = strtoupper($operator);
        return $this;
    }

    /**
     * Retorna todos os filtros
     * 
     * @return QueryFilter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Retorna ordenações
     * 
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Retorna limite
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Retorna offset
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Retorna agrupamentos
     * 
     * @return array
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /**
     * Gera a cláusula WHERE SQL e retorna os parâmetros
     * 
     * @return array ['sql' => string, 'params' => array]
     */
    public function toSQL(): array
    {
        if (empty($this->filters)) {
            return ['sql' => '', 'params' => []];
        }

        $conditions = [];
        $params = [];

        foreach ($this->filters as $index => $filter) {
            $filterSQL = $filter->toSQL();
            $logicalOp = $index > 0 ? $filter->getLogicalOperator() : '';
            
            if ($logicalOp) {
                $conditions[] = " {$logicalOp} " . $filterSQL['sql'];
            } else {
                $conditions[] = $filterSQL['sql'];
            }
            
            $params = array_merge($params, $filterSQL['params']);
        }

        $sql = 'WHERE ' . implode('', $conditions);
        
        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Gera a cláusula ORDER BY SQL
     * 
     * @return string
     */
    public function toOrderBySQL(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $orders = [];
        foreach ($this->orderBy as $order) {
            $field = $this->escapeField($order['field']);
            $direction = $order['direction'] === 'DESC' ? 'DESC' : 'ASC';
            $orders[] = "{$field} {$direction}";
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Gera a cláusula GROUP BY SQL
     * 
     * @return string
     */
    public function toGroupBySQL(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }

        $fields = array_map(fn($f) => $this->escapeField($f), $this->groupBy);
        return 'GROUP BY ' . implode(', ', $fields);
    }

    /**
     * Escapa nome de campo/coluna
     */
    private function escapeField(string $field): string
    {
        if (preg_match('/^[`"]/', $field)) {
            return $field;
        }
        
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            return implode('.', array_map(fn($p) => "`{$p}`", $parts));
        }
        
        return "`{$field}`";
    }
}

