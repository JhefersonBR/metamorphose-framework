<?php

return [
    'enabled' => filter_var(getenv('LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
    'channel' => getenv('LOG_CHANNEL') ?: 'metamorphose',
    'level' => getenv('LOG_LEVEL') ?: 'info',
    'path' => getenv('LOG_PATH') ?: __DIR__ . '/../storage/logs',
    'http_log' => filter_var(getenv('HTTP_LOG_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
];

