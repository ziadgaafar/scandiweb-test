<?php

namespace App\GraphQL\Exception;

class InvalidQuantityException extends GraphQLException
{
    public function __construct(string $productId, int $quantity)
    {
        parent::__construct(
            "Invalid quantity ({$quantity}) for product {$productId}. Quantity must be greater than 0.",
            'user',
            'INVALID_QUANTITY',
            400
        );
    }
}
