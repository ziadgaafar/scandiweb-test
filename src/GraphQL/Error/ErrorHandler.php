<?php

namespace App\GraphQL\Error;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use RuntimeException;

class ErrorHandler
{
    public static function formatError(Error $error): array
    {
        $formattedError = FormattedError::createFromException($error);
        $previous = $error->getPrevious();

        // Handle specific error types
        if ($previous instanceof RuntimeException) {
            $formattedError['extensions'] = [
                'category' => 'business',
                'code' => self::getErrorCode($previous->getMessage())
            ];
        }

        return $formattedError;
    }

    private static function getErrorCode(string $message): string
    {
        $errorCodes = [
            'Product not found' => 'PRODUCT_NOT_FOUND',
            'Product is out of stock' => 'PRODUCT_OUT_OF_STOCK',
            'Invalid attribute selection' => 'INVALID_ATTRIBUTES',
            'Product attributes must be selected' => 'MISSING_ATTRIBUTES',
            'Quantity must be greater than 0' => 'INVALID_QUANTITY'
        ];

        return $errorCodes[$message] ?? 'INTERNAL_ERROR';
    }
}
