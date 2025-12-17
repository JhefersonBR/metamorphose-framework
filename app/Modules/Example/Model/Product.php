<?php

namespace Metamorphose\Modules\Example\Model;

use Metamorphose\Kernel\Model\AbstractModel;

/**
 * Model Product
 * 
 * Exemplo de model usando AbstractModel
 */
class Product extends AbstractModel
{
    protected static string $table = 'products';
    protected static string $primaryKey = 'id';
    protected static string $scope = 'tenant';
}

