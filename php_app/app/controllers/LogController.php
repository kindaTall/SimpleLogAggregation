<?php

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\LogModel;

use PDO;

class LogController
{
    private $app;
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    

    public function addLog(Request $request, Response $response): Response
    {
        $body = $request->getBody();
        $data = json_decode($body, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => 'Invalid JSON']));
            return $response;
        }

        $log = new LogModel($this->pdo);
        $log->host = $data['host'] ?? 'unknown';
        $log->host_process = $data['host_process'] ?? null;
        $log->log_level = $data['log_level'] ?? 'INFO';
        $log->log_message = $data['log_message'] ?? '';
        $log->timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

        $logId = $log->save();

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['id' => $logId]));

        return $response->withStatus(201);
    }

    public function getLogs(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        $host = $queryParams['host'] ?? null;
        $hostProcess = $queryParams['host_process'] ?? null;
        $logLevel = $queryParams['log_level'] ?? null;
        $timestampFrom = $queryParams['timestamp_from'] ?? null;
        $timestampTo = $queryParams['timestamp_to'] ?? null;

        $logs = LogModel::getLogs($this->pdo, $host, $hostProcess, $logLevel, $timestampFrom, $timestampTo);

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($logs));

        return $response;
    }

    public function viewLogs(Request $request, Response $response, Container $container): Response
    {
        $logs = LogModel::getLogs($this->pdo);

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
        $twig = new \Twig\Environment($loader);

        $template = $twig->load('logs.twig');
        $response->getBody()->write($template->render(['logs' => $logs]));
        return $response;
    }
}
