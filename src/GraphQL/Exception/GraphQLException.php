<?php

namespace App\GraphQL\Exception;

use GraphQL\Error\ClientAware;

class GraphQLException extends \Exception implements ClientAware
{
    private string $errorCategory;
    private string $errorCode;
    private int $httpStatusCode;

    public function __construct(
        string $message,
        string $category = 'user',
        string $errorCode = 'UNKNOWN_ERROR',
        int $httpStatusCode = 400
    ) {
        parent::__construct($message);
        $this->errorCategory = $category;
        $this->errorCode = $errorCode;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getCategory(): string
    {
        return $this->errorCategory;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
