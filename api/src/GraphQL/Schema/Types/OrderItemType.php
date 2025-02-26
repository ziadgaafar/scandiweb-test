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
                    'description' => 'The product ordered',
                    'resolve' => function ($orderItem) {
                        return $this->typeRegistry
                            ->getResolver('product')
                            ->getProduct($orderItem['product_id']);
                    }
                ],
                'quantity' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Quantity of the product'
                ],
                'selectedAttributes' => [
                    'type' => Type::listOf($this->typeRegistry->getType('selectedAttribute')),
                    'description' => 'Selected attribute values for the product',
                    'resolve' => function ($orderItem) {
                        return isset($orderItem['selected_attributes'])
                            ? json_decode($orderItem['selected_attributes'], true)
                            : null;
                    }
                ],
                'unitPrice' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Price per unit',
                    'resolve' => function ($orderItem) {
                        return (float) $orderItem['unit_price'];
                    }
                ],
                'total' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Total price for this item',
                    'resolve' => function ($orderItem) {
                        return (float) ($orderItem['unit_price'] * $orderItem['quantity']);
                    }
                ]
            ]
        ];

        parent::__construct($config);
    }
}
