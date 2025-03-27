<?php

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';


$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->getContainer()->set(Psr\Http\Message\ResponseFactoryInterface::class, function () {
    return new Slim\Psr7\Factory\ResponseFactory();
});


// Load database configuration
$dbConfig = require __DIR__ . '/../config/database.php';
$dbConfig($app);

// Load Twig configuration
$twigConfig = require __DIR__ . '/../config/twig.php';
$twigConfig($app);

// Add Routing Middleware
$app->addRoutingMiddleware();


// Add Error Handling Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Include routes
$routes = require __DIR__ . '/routes.php';
$routes($app);

return $app;
