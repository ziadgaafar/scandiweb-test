<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'Order',
            'description' => 'A customer order',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::id()),
                    'description' => 'The unique identifier of the order'
                ],
                'items' => [
                    'type' => Type::nonNull(Type::listOf($this->typeRegistry->getType('orderItem'))),
                    'description' => 'The items in the order'
                ],
                'total' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'The total cost of the order',
                    'resolve' => function ($order) {
                        return (float) $order['total'];
                    }
                ],
                'createdAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'When the order was created'
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Current status of the order'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
