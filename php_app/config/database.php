<?php

use Dotenv\Dotenv;
use Slim\App;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return function (App $app) {
    $container = $app->getContainer();
    $container->set(PDO::class, function ($c) {
        $dbConfig = [
            'default' => $_ENV['DB_CONNECTION'],
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'] ?? null,
                    'database' => $_ENV['DB_DATABASE'] ?? null,
                    'username' => $_ENV['DB_USERNAME'] ?? null ,
                    'password' => $_ENV['DB_PASSWORD'] ?? null ,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ],
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => __DIR__ . '/../' . $_ENV['DB_DATABASE'] ?? "logs.sqlite",
                    'prefix' => '',
                ],
            ],
        ];

        $connection = $dbConfig['connections'][$dbConfig['default']];
        $pdo = null;
        switch ($connection['driver']) {
            case 'mysql':
                $dsn = "mysql:host={$connection['host']};dbname={$connection['database']};charset={$connection['charset']}";
                $pdo = new PDO($dsn, $connection['username'], $connection['password']);
                break;
            case 'sqlite':
                $dsn = "sqlite:" . $connection['database'];
                $pdo = new PDO($dsn);
                break;
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    });
};
