<?php

return [
    'name' => 'Metamorphose Framework',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
];

