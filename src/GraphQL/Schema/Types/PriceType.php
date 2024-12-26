<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PriceType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'Price',
            'description' => 'A product price with currency',
            'fields' => [
                'amount' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'The price amount'
                ],
                'currency' => [
                    'type' => Type::nonNull($this->typeRegistry->getType('currency')),
                    'description' => 'The price currency'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
