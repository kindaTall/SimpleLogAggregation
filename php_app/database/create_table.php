<?php

require __DIR__ . '/../vendor/autoload.php';

$config = include __DIR__ . '/../config/database.php';

$db = new PDO('sqlite:' . $config['connections']['sqlite']['database']);
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
