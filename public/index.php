<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Incluir arquivos do Bootstrap (contêm funções, não classes)
require_once __DIR__ . '/../app/Bootstrap/container.php';
require_once __DIR__ . '/../app/Bootstrap/app.php';
require_once __DIR__ . '/../app/Bootstrap/middleware.php';
require_once __DIR__ . '/../app/Bootstrap/routes.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);
Bootstrap\loadRoutes($app, $container);

$app->run();

