<?php

namespace App\GraphQL\Exception;

class ProductNotFoundException extends GraphQLException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            "Product not found: {$productId}",
            'user',
            'PRODUCT_NOT_FOUND',
            404
        );
    }
}
