<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Output an environment variable
    echo "OPENAI_API_KEY: " . $_ENV['OPENAI_API_KEY'] . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
