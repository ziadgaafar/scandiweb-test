<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class OrderItemInputType extends InputObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'OrderItemInput',
            'description' => 'Input for an order item',
            'fields' => [
                'productId' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The ID of the product to order'
                ],
                'quantity' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Quantity of the product'
                ],
                'selectedAttributes' => [
                    'type' => Type::listOf($this->typeRegistry->getType('selectedAttributeInput')),
                    'description' => 'Selected attribute values for the product'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
