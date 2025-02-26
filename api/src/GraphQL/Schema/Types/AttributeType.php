<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AttributeType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'Attribute',
            'description' => 'A product attribute value',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The identifier of the attribute'
                ],
                'displayValue' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The display value of the attribute'
                ],
                'value' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The actual value of the attribute'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
