<?php

namespace Metamorphose\Kernel\Database\Query;

/**
 * Representa um filtro de consulta
 * 
 * Gera SQL e parâmetros compatíveis com Doctrine DBAL
 */
class QueryFilter
{
    private string $field;
    private string $operator;
    private mixed $value;
    private ?string $logicalOperator;

    /**
     * @param string $field Campo a ser filtrado
     * @param string $operator Operador: =, !=, <, >, <=, >=, LIKE, IN, NOT IN, IS NULL, IS NOT NULL
     * @param mixed $value Valor para comparação
     * @param string|null $logicalOperator Operador lógico: AND ou OR (padrão: AND)
     */
    public function __construct(
        string $field,
        string $operator,
        mixed $value = null,
        ?string $logicalOperator = null
    ) {
        $this->field = $field;
        $this->operator = strtoupper(trim($operator));
        $this->value = $value;
        $this->logicalOperator = $logicalOperator ? strtoupper(trim($logicalOperator)) : 'AND';
    }

    /**
     * Retorna o campo
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Retorna o operador
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Retorna o valor
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Retorna o operador lógico
     */
    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }

    /**
     * Gera a condição SQL e retorna os parâmetros
     * 
     * @return array ['sql' => string, 'params' => array]
     */
    public function toSQL(): array
    {
        $field = $this->escapeField($this->field);
        $operator = $this->operator;
        $params = [];
        $paramName = 'param_' . uniqid();

        switch ($operator) {
            case 'IS NULL':
            case 'IS NOT NULL':
                return [
                    'sql' => "{$field} {$operator}",
                    'params' => [],
                ];

            case 'IN':
            case 'NOT IN':
                if (!is_array($this->value)) {
                    throw new \InvalidArgumentException("Operator {$operator} requires an array value");
                }
                $placeholders = [];
                foreach ($this->value as $index => $val) {
                    $placeholder = $paramName . '_' . $index;
                    $placeholders[] = ':' . $placeholder;
                    $params[$placeholder] = $val;
                }
                $placeholdersStr = implode(', ', $placeholders);
                return [
                    'sql' => "{$field} {$operator} ({$placeholdersStr})",
                    'params' => $params,
                ];

            case 'BETWEEN':
                if (!is_array($this->value) || count($this->value) !== 2) {
                    throw new \InvalidArgumentException("Operator BETWEEN requires an array with 2 values");
                }
                return [
                    'sql' => "{$field} BETWEEN :{$paramName}_0 AND :{$paramName}_1",
                    'params' => [
                        $paramName . '_0' => $this->value[0],
                        $paramName . '_1' => $this->value[1],
                    ],
                ];

            default:
                // =, !=, <, >, <=, >=, LIKE
                return [
                    'sql' => "{$field} {$operator} :{$paramName}",
                    'params' => [$paramName => $this->value],
                ];
        }
    }

    /**
     * Escapa nome de campo/coluna
     */
    private function escapeField(string $field): string
    {
        // Se já contém backticks ou aspas, retorna como está
        if (preg_match('/^[`"]/', $field)) {
            return $field;
        }
        
        // Se contém ponto, pode ser tabela.coluna
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            return implode('.', array_map(fn($p) => "`{$p}`", $parts));
        }
        
        return "`{$field}`";
    }
}

