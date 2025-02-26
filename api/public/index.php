<?php

/**
 * Entry point for the Scandiweb Test Application
 * 
 * This file serves as the main entry point for all requests.
 * It bootstraps the application and handles the request.
 */

// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Require composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Bootstrap and run the application
try {
    App\Core\App::getInstance()->run();
} catch (Throwable $e) {
    // Log the error if in production
    if (getenv('APP_ENV') !== 'development') {
        error_log($e->getMessage());
    }

    // Send generic error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'errors' => [
            [
                'message' => 'Internal Server Error',
                'extensions' => [
                    'code' => 'INTERNAL_ERROR'
                ]
            ]
        ]
    ]);
}
