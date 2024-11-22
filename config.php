<?php
// config.php

// Include the Composer autoloader
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Initialize Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Access environment variables
$apiKey = $_ENV['OPENAI_API_KEY'];

echo "Your API Key: " . $apiKey;
?>