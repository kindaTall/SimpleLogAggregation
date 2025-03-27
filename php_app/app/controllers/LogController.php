<?php

namespace App\Controllers;


use PDO;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\LogModel;
use Twig\Environment as TwigEnvironment; // Alias to avoid naming conflict if needed
use OpenApi\Attributes as OA; // Import Swagger annotations


#[OA\Info(title: "Simple Log Aggregation API", version: "1.0")]
class LogController
{
    private $pdo;
    private $twig;


    public function __construct(PDO $pdo, TwigEnvironment $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    #[OA\Post(
        path: "/api/logs",
        summary: "Add a new log entry",
        description: "Receives log data in JSON format and stores it.",
        requestBody: new OA\RequestBody(
            description: "Log data",
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "host", type: "string", example: "server1.example.com"),
                    new OA\Property(property: "host_process", type: "string", example: "nginx"),
                    new OA\Property(property: "log_level", type: "string", example: "ERROR"),
                    new OA\Property(property: "log_message", type: "string", example: "Failed to connect to database."),
                    new OA\Property(property: "timestamp", type: "string", format: "date-time", example: "2023-10-27T10:00:00Z")
                ],
                type: "object"
            )
        ),
        tags: ["Logs"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Log created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 123)
                    ],
                    type: "object"
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid JSON input",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid JSON")
                    ],
                    type: "object"
                )
            )
        ]
    )]
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

    #[OA\Get(
        path: "/api/logs",
        summary: "Retrieve log entries",
        description: "Returns a list of log entries, optionally filtered by query parameters.",
        tags: ["Logs"],
        parameters: [
            new OA\Parameter(name: "host", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filter by host name"),
            new OA\Parameter(name: "host_process", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filter by host process"),
            new OA\Parameter(name: "log_level", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filter by log level"),
            new OA\Parameter(name: "timestamp_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date-time"), description: "Filter logs from this timestamp (inclusive)"),
            new OA\Parameter(name: "timestamp_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date-time"), description: "Filter logs up to this timestamp (inclusive)")
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of log entries",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer"),
                            new OA\Property(property: "host", type: "string"),
                            new OA\Property(property: "host_process", type: "string"),
                            new OA\Property(property: "log_level", type: "string"),
                            new OA\Property(property: "log_message", type: "string"),
                            new OA\Property(property: "timestamp", type: "string", format: "date-time")
                        ],
                        type: "object"
                    )
                )
            )
        ]
    )]
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

    public function viewLogs(Request $request, Response $response): Response
    {
        $logs = LogModel::getLogs($this->pdo);

        // Use the injected twig environment
        $template = $this->twig->load('logs.twig');
        $response->getBody()->write($template->render(['logs' => $logs]));
        return $response;
    }

    #[OA\Get(
        path: "/api/health",
        summary: "Check the health of the API",
        description: "Returns a simple status message to indicate if the API is operational.",
        tags: ["Health"],
        responses: [
            new OA\Response(
                response: 200,
                description: "API status is OK",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "OK")
                    ],
                    type: "object"
                )
            )
        ]
    )]
    public function healthCheck(Request $request, Response $response): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(['status' => 'OK']));
        return $response;
    }
}
