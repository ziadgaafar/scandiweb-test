<?php

namespace App\GraphQL\Error;

use App\GraphQL\Exception\GraphQLException;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;

class ErrorHandler
{
    public static function formatError(Error $error): array
    {
        $formattedError = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        if ($previous instanceof GraphQLException) {
            $formattedError['extensions'] = [
                'category' => $previous->getCategory(),
                'code' => $previous->getErrorCode()
            ];

            // Set HTTP status code
            http_response_code($previous->getHttpStatusCode());
        } else {
            // For unexpected errors, use 500 status code
            http_response_code(500);
            $formattedError['extensions'] = [
                'category' => 'internal',
                'code' => 'INTERNAL_ERROR'
            ];
        }

        return $formattedError;
    }
}
