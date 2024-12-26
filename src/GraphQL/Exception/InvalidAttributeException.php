<?php

namespace App\GraphQL\Exception;

class InvalidAttributeException extends GraphQLException
{
    public function __construct(string $message)
    {
        parent::__construct(
            $message,
            'user',
            'INVALID_ATTRIBUTE',
            400
        );
    }
}
