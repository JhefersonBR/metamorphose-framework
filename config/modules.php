<?php

return [
    'enabled' => [
        // Formato 1: Módulo local (apenas classe)
        // \Metamorphose\Modules\Example\Module::class,
        
        // Formato 2: Módulo local (com configuração explícita)
        // [
        //     'class' => \Metamorphose\Modules\Example\Module::class,
        //     'name' => 'example', // opcional, extraído do namespace se não fornecido
        //     'mode' => 'local', // padrão: 'local'
        // ],
        
        // Formato 3: Módulo remoto (microserviço)
        // [
        //     'class' => \Metamorphose\Modules\Permission\Module::class,
        //     'name' => 'permission',
        //     'mode' => 'remote',
        //     'endpoint' => getenv('PERMISSION_SERVICE_URL') ?: 'http://permission-service:8000',
        //     'timeout' => 30, // opcional, padrão: 30 segundos
        //     'headers' => [ // opcional, headers customizados
        //         'X-API-Key' => getenv('PERMISSION_API_KEY'),
        //     ],
        // ],
    ],
];
