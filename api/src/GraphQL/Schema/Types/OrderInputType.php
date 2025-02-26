<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class OrderInputType extends InputObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'OrderInput',
            'description' => 'Input for creating a new order',
            'fields' => [
                'items' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull($this->typeRegistry->getType('orderItemInput')))),
                    'description' => 'The items to order'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
