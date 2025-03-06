<?php

namespace App\Core;

use App\Config\Environment;
use App\Services\Database\MySQLConnection;

class App
{
    private static ?App $instance = null;
    private Router $router;
    private Environment $environment;

    private function __construct()
    {
        // Load environment variables
        $this->environment = new Environment();
        $this->environment->load();

        // Initialize error handling based on environment
        $this->initializeErrorHandling();

        // Initialize database connection
        MySQLConnection::getInstance();

        // Initialize router
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeErrorHandling(): void
    {
        if ($this->environment->get('APP_DEBUG', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function handleError($severity, $message, $file, $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(\Throwable $exception): void
    {
        $isDebug = $this->environment->get('APP_DEBUG', false);

        http_response_code(500);
        header('Content-Type: application/json');

        echo json_encode([
            'errors' => [
                [
                    'message' => $isDebug ? $exception->getMessage() : 'Internal Server Error',
                    'extensions' => $isDebug ? [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTrace()
                    ] : null
                ]
            ]
        ]);
    }

    public function run(): void
    {
        try {
            // Handle CORS
            $this->handleCORS();

            // Process the request
            $this->router->dispatch();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleCORS(): void
    {
        // Allow CORS for all origins
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }
}
