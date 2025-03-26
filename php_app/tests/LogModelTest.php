<?php

use PHPUnit\Framework\TestCase;
use App\Models\LogModel;

class LogModelTest extends TestCase
{
    public function testSaveLog(): void
    {
        $log = new LogModel();
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
        $log = new LogModel();
        $log->host = 'test-server';
        $log->host_process = 'api';
        $log->log_level = 'INFO';
        $log->log_message = 'Test message';
        $log->timestamp = date('Y-m-d H:i:s');

        $log->save();

        $logs = LogModel::getLogs('test-server', 'api', 'INFO');

        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        $this->assertEquals('test-server', $logs[0]['host']);
        $this->assertEquals('api', $logs[0]['host_process']);
        $this->assertEquals('INFO', $logs[0]['log_level']);
        $this->assertEquals('Test message', $logs[0]['log_message']);
    }
}
