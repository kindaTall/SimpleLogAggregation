<?php

use Slim\App;
use App\Controllers\LogController;
use Twig\Environment as TwigEnvironment; 


return function (App $app) {
    $app->get('/', function ($request, $response, $args)  { 
        $twig = $this->get(TwigEnvironment::class); 
        $template = $twig->load('home.twig');
        $response->getBody()->write($template->render(['name' => 'SimpleLogAggregation']));
        return $response;
    })->setName('home');

    $app->post('/api/logs', [LogController::class, 'addLog'])->setName('addLog');
    $app->get('/api/logs', [LogController::class, 'getLogs'])->setName('getLogs');
    $app->get('/logs', [LogController::class, 'viewLogs'])->setName('viewLogs');
    $app->get('/api/health', [LogController::class, 'healthCheck'])->setName('healthCheck');
};
