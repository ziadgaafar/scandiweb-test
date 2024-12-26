<?php

namespace App\GraphQL\Exception;

class MissingAttributesException extends GraphQLException
{
    public function __construct(string $productId, array $missingAttributes)
    {
        $attributeList = implode(', ', $missingAttributes);
        parent::__construct(
            "Missing required attributes for product {$productId}: {$attributeList}",
            'user',
            'MISSING_REQUIRED_ATTRIBUTES',
            400
        );
    }
}
