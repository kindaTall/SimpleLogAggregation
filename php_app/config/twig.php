<?php

use Slim\App;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Psr\Container\ContainerInterface;

return function (App $app) {
    $container = $app->getContainer();

    $container->set(Environment::class, function (ContainerInterface $c) {
        // Define the path to the views directory relative to this config file's parent directory
        $viewPath = __DIR__ . '/../app/views';
        $loader = new FilesystemLoader($viewPath);

        // You can add options here, like caching, debug mode, etc.
        // Example: $twig = new Environment($loader, ['cache' => __DIR__ . '/../cache/twig']);
        $twig = new Environment($loader, [
            // Add cache path if needed, e.g., 'cache' => __DIR__ . '/../var/cache/twig'
            // Enable debug if needed, e.g., 'debug' => true (requires twig/extra-bundle)
        ]);

        // Add extensions if needed, e.g., $twig->addExtension(new \Twig\Extension\DebugExtension());

        return $twig;
    });
};
