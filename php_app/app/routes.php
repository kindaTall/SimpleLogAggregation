<?php

use Slim\App;
use App\Controllers\LogController;

return function (App $app) {
    $app->post('/api/logs', [LogController::class, 'addLog'])->setName('addLog');
    $app->get('/api/logs', [LogController::class, 'getLogs'])->setName('getLogs');
    $app->get('/logs', [LogController::class, 'viewLogs'])->setName('viewLogs');
};
