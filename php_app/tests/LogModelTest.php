<?php

use PHPUnit\Framework\TestCase;
use App\Models\LogModel;

class LogModelTest extends TestCase
{
    private $app;
    private $pdo;

    protected function setUp(): void
    {
        // Load the application and database connection
        $this->app = require __DIR__ . '/../app/bootstrap.php';
        $routes = $app->getRouteCollector()->getRoutes();
        $this->pdo = $this->app->getContainer()->get('pdo');
    }
    

    public function testSaveLog(): void
    {
        $log = new LogModel($this->pdo);
        $log->host = 'test-server';
        $log->host_process = 'api';
        $log->log_level = 'INFO';
        $log->log_message = 'Test message';
        $log->timestamp = date('Y-m-d H:i:s');

        $logId = $log->save();

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);
    }

    public function testGetLogs(): void
    {
        $log = new LogModel($this->pdo);
        $log->host = 'test-server';
        $log->host_process = 'api';
        $log->log_level = 'INFO';
        $log->log_message = 'Test message';
        $log->timestamp = date('Y-m-d H:i:s');

        $log->save();

        $logs = LogModel::getLogs($this->pdo, 'test-server', 'api', 'INFO');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        $this->assertEquals('test-server', $logs[0]['host']);
        $this->assertEquals('api', $logs[0]['host_process']);
        $this->assertEquals('INFO', $logs[0]['log_level']);
        $this->assertEquals('Test message', $logs[0]['log_message']);
    }
}
