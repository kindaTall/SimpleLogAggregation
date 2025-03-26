<?php

use Slim\App;
use App\Controllers\LogController;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

return function (App $app) {
    $app->get('/', function ($request, $response, $args) {
        $loader = new FilesystemLoader(__DIR__ . '/views');
        $twig = new Environment($loader);

        $template = $twig->load('home.twig');
        $response->getBody()->write($template->render(['name' => 'SimpleLogAggregation']));
        return $response;
    });

    $app->post('/api/logs', [LogController::class, 'addLog'])->setName('addLog');
    $app->get('/api/logs', [LogController::class, 'getLogs'])->setName('getLogs');
    $app->get('/logs', [LogController::class, 'viewLogs'])->add(function ($request, $handler) use ($app) {
        $container = $app->getContainer();
        $controller = new \App\Controllers\LogController($container->get('PDO'));
        $response = $controller->viewLogs($request, new \Slim\Psr7\Response(), $container);
        return $response;
    })->setName('viewLogs');
};
