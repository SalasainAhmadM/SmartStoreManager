<?php
require 'vendor/autoload.php'; // Composer's autoloader

use OpenAI\Factory;
use Dotenv\Dotenv;

// Load the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;

if (!$apiKey) {
    echo json_encode(['error' => 'API key not configured.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business = filter_input(INPUT_POST, 'business', FILTER_SANITIZE_STRING);

    if (!$business) {
        echo json_encode(['error' => 'Invalid or missing business name.']);
        exit;
    }

    try {
        // Initialize OpenAI client
        $client = OpenAI::client($apiKey);


        $response = $client->chat()->create([
            'model' => 'Zapier',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that provides actionable business insights.',
                ],
                [
                    'role' => 'user',
                    'content' => "Provide actionable sales insights for the business named '$business'. Be specific and practical.",
                ],
            ],
            'max_tokens' => 150,
        ]);

        // Extract insight from the response
        $insight = $response['choices'][0]['message']['content'] ?? 'No insights available.';
        echo json_encode(['choices' => [['text' => $insight]]]);
    } catch (Exception $e) {
        // Log error and return a friendly message
        error_log('OpenAI Error: ' . $e->getMessage());
        echo json_encode(['error' => 'Error generating insights: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
