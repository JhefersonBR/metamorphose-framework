<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Metamorphose\Bootstrap;

$container = Bootstrap\buildContainer();
$app = Bootstrap\createApp($container);

Bootstrap\registerMiddlewares($app, $container);
Bootstrap\loadRoutes($app, $container);

$app->run();

