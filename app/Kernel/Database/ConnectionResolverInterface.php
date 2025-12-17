<?php

namespace Metamorphose\Kernel\Database;

use PDO;

/**
 * Interface para resolver conexões de banco de dados
 */
interface ConnectionResolverInterface
{
    public function resolveCore(): PDO;
    
    public function resolveTenant(?string $tenantId = null): PDO;
    
    public function resolveUnit(?string $unitId = null): PDO;
}

