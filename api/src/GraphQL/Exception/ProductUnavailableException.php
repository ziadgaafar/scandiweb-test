<?php

namespace App\GraphQL\Exception;

class ProductUnavailableException extends GraphQLException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            "Product is not available: {$productId}",
            'user',
            'PRODUCT_UNAVAILABLE',
            400
        );
    }
}
