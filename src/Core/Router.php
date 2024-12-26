<?php

namespace App\Core;

use App\Services\GraphQL\GraphQLService;

class Router
{
    private string $requestMethod;
    private string $requestPath;

    public function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function dispatch(): void
    {
        try {
            if ($this->requestPath === '/graphql') {
                $this->handleGraphQLRequest();
                return;
            }

            // Handle 404 for non-GraphQL routes
            $this->handle404();
        } catch (\Throwable $e) {
            // Let App class handle any uncaught exceptions
            throw $e;
        }
    }

    private function handleGraphQLRequest(): void
    {
        // Only allow POST requests for GraphQL
        if ($this->requestMethod !== 'POST') {
            $this->handle405();
            return;
        }

        // Get the raw input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!is_array($input) || !isset($input['query'])) {
            $this->handleBadRequest();
            return;
        }

        // Execute GraphQL query
        $variables = $input['variables'] ?? null;
        $result = GraphQLService::getInstance()->executeQuery($input['query'], $variables);

        // Send response
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    private function handle404(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'errors' => [
                [
                    'message' => 'Not Found',
                    'extensions' => [
                        'category' => 'user',
                        'code' => 'NOT_FOUND'
                    ]
                ]
            ]
        ]);
    }

    private function handle405(): void
    {
        http_response_code(405);
        header('Content-Type: application/json');
        header('Allow: POST');
        echo json_encode([
            'errors' => [
                [
                    'message' => 'Method Not Allowed',
                    'extensions' => [
                        'category' => 'user',
                        'code' => 'METHOD_NOT_ALLOWED'
                    ]
                ]
            ]
        ]);
    }

    private function handleBadRequest(): void
    {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'errors' => [
                [
                    'message' => 'Invalid request',
                    'extensions' => [
                        'category' => 'user',
                        'code' => 'BAD_REQUEST'
                    ]
                ]
            ]
        ]);
    }
}
