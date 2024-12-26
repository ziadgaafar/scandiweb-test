<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderItemType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'OrderItem',
            'description' => 'An item in an order',
            'fields' => [
                'product' => [
                    'type' => Type::nonNull($this->typeRegistry->getType('product')),
                    'description' => 'The product ordered'
                ],
                'quantity' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Quantity of the product'
                ],
                'selectedAttributes' => [
                    'type' => Type::listOf($this->typeRegistry->getType('selectedAttribute')),
                    'description' => 'Selected attribute values for the product'
                ],
                'unitPrice' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Price per unit'
                ],
                'total' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Total price for this item'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
