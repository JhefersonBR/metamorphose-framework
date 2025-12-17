<?php

return [
    'core' => [
        'driver' => getenv('DB_CORE_DRIVER') ?: 'mysql',
        'host' => getenv('DB_CORE_HOST') ?: 'localhost',
        'port' => getenv('DB_CORE_PORT') ?: 3306,
        'database' => getenv('DB_CORE_DATABASE') ?: 'metamorphose_core',
        'username' => getenv('DB_CORE_USERNAME') ?: 'root',
        'password' => getenv('DB_CORE_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'tenant' => [
        'driver' => getenv('DB_TENANT_DRIVER') ?: 'mysql',
        'host' => getenv('DB_TENANT_HOST') ?: 'localhost',
        'port' => getenv('DB_TENANT_PORT') ?: 3306,
        'database' => getenv('DB_TENANT_DATABASE') ?: 'metamorphose_tenant',
        'username' => getenv('DB_TENANT_USERNAME') ?: 'root',
        'password' => getenv('DB_TENANT_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'unit' => [
        'driver' => getenv('DB_UNIT_DRIVER') ?: 'mysql',
        'host' => getenv('DB_UNIT_HOST') ?: 'localhost',
        'port' => getenv('DB_UNIT_PORT') ?: 3306,
        'database' => getenv('DB_UNIT_DATABASE') ?: 'metamorphose_unit',
        'username' => getenv('DB_UNIT_USERNAME') ?: 'root',
        'password' => getenv('DB_UNIT_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
];

