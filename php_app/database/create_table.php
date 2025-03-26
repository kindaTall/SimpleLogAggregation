<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dbConnection = $_ENV['DB_CONNECTION'];

if ($dbConnection === 'sqlite') {
    $db = new PDO('sqlite:' . __DIR__ . '/../' . $_ENV['DB_DATABASE']);
} elseif ($dbConnection === 'mysql') {
    $host = $_ENV['MYSQL_DB_HOST'];
    $dbname = $_ENV['MYSQL_DB_DATABASE'];
    $username = $_ENV['MYSQL_DB_USERNAME'];
    $password = $_ENV['MYSQL_DB_PASSWORD'];

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Invalid DB_CONNECTION: " . $dbConnection . "\n";
    exit(1);
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "
CREATE TABLE IF NOT EXISTS logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  host VARCHAR(100) NOT NULL,
  host_process VARCHAR(100),
  log_level VARCHAR(20) NOT NULL,
  log_message TEXT NOT NULL,
  timestamp DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $db->exec($sql);
    echo "Table 'logs' created successfully!\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
