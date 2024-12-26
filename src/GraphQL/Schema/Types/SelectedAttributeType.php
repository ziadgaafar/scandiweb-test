<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;


class SelectedAttributeType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'SelectedAttribute',
            'description' => 'A selected attribute value for an order item',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The attribute identifier'
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The attribute name'
                ],
                'value' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The selected value'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
