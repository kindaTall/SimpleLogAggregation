<?php

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Stream;
use Slim\Psr7\Response;
use Slim\Psr7\Headers;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Routing\RouteContext;

class LogTest extends TestCase
{
    private $process;
    private $pipes;

    private $port = 8071; // Port for the PHP server
    private $app = null;
    private $routeParser = null;

    protected function setUp(): void
    {
        $command = 'php -S localhost:' . $this->port . ' -t public';
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")   // stderr is a pipe that the child will write to
        );
        $this->process = proc_open($command, $descriptorspec, $this->pipes);

        if (!is_resource($this->process)) {
            $this->fail("Failed to start the PHP server.");
        }

        $app = require __DIR__ . '/../app/bootstrap.php'; 
        $this->app = $app;
        // Build app router naming resolver
        $this->routeParser = $app->getRouteCollector()->getRouteParser();

        // Give the server some time to start
        sleep(1);
    }

    // New private helper function
    private function getUrl(string $name, array $data = [], array $queryParams = []): string
    {
        $relativeUrl = $this->routeParser->urlFor($name, $data, $queryParams);
        return 'http://localhost:'. $this->port . $relativeUrl;
    }

    protected function kill($process) {
        if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
            $status = proc_get_status($process);
            return exec('taskkill /F /T /PID '.$status['pid']);
        } else {
            return proc_terminate($process);
        }
    }

    protected function tearDown(): void
    {
        if (is_resource($this->process)) {
            fclose($this->pipes[0]);
            fclose($this->pipes[1]);
            fclose($this->pipes[2]);
            $this->kill($this->process);
        }
    }


    public function testViewLogs()
    {
        // Test the /logs endpoint, which is a pure GET endpoint
        $url = $this->getUrl('viewLogs');
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->assertEquals(200, $httpCode, "Expected HTTP 200 status code");
        
        // Close cURL session
        curl_close($ch);
        
        $this->assertNotEmpty($response, "Response is empty.");
        $this->assertStringContainsString('<!DOCTYPE html>', $response, "Response does not contain expected HTML content.");
        $this->assertStringContainsString('<h1>Logs</h1>', $response, "Response does not contain expected HTML content.");
        $this->assertStringContainsString('<table id="example"', $response, "Response does not contain expected HTML table.");
        $this->assertStringContainsString('</table>', $response, "Response does not contain expected HTML table.");
        $this->assertStringContainsString('<footer>', $response, "Response does not contain expected HTML footer.");
    }

    public function testAddLog(): void
    {
        // Test the /logs endpoint, which is a pure POST endpoint
        // Prepare the URL and data for the POST request
        $url = $this->getUrl('addLog');

        $data = [
            "host" => "test-server",
            "host_process" => "api",
            "log_level" => "INFO",
            "log_message" => "Test message"
        ];
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
        
        // Execute the request
        $curlOutput = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->assertEquals(201, $httpCode, "Expected HTTP 200 status code");
        
        // Close cURL session
        curl_close($ch);

        $this->assertNotEmpty($curlOutput, "cURL output is empty.");
        $response = json_decode($curlOutput, true);
        $this->assertArrayHasKey('id', $response, "Response does not contain 'id' key.");
    }

    public function testGetLogs(): void
    {
        // Test the /logs endpoint, which is a pure GET endpoint
        $url = $this->getUrl('getLogs');
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $curlOutput = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->assertEquals(200, $httpCode, "Expected HTTP 200 status code");
        
        // Close cURL session
        curl_close($ch);

        $this->assertNotEmpty($curlOutput, "cURL output is empty.");
    }

    public function testHealthCheck(): void
    {
        // Test the /api/health endpoint, which is a pure GET endpoint
        $url = 'http://localhost:'. $this->port . '/api/health';
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $curlOutput = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->assertEquals(200, $httpCode, "Expected HTTP 200 status code");
        
        // Close cURL session
        curl_close($ch);

        $this->assertNotEmpty($curlOutput, "cURL output is empty.");
        $response = json_decode($curlOutput, true);
        $this->assertArrayHasKey('status', $response, "Response does not contain 'status' key.");
        $this->assertEquals('OK', $response['status'], "Response status is not 'OK'.");
    }

    public function testHomeRoute(): void
    {
        // Test the / endpoint (named 'home')
        $url = $this->getUrl('home');
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $this->fail('cURL error: ' . curl_error($ch));
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->assertEquals(200, $httpCode, "Expected HTTP 200 status code for home route");
        
        // Close cURL session
        curl_close($ch);
        
        $this->assertNotEmpty($response, "Home route response is empty.");
        $this->assertStringContainsString('<!DOCTYPE html>', $response, "Home response does not contain expected HTML content.");
        $this->assertStringContainsString('<title>SimpleLogAggregation</title>', $response, "Home response does not contain expected title.");
        // Check for a specific string rendered by Twig
        $this->assertStringContainsString('Welcome to the SimpleLogAggregation', $response, "Home response does not contain expected welcome message."); 
        $this->assertStringContainsString('<footer>', $response, "Home response does not contain expected HTML footer.");
    }
}
