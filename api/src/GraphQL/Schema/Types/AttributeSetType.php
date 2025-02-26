<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AttributeSetType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'AttributeSet',
            'description' => 'A set of product attributes',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The identifier of the attribute set'
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The name of the attribute set'
                ],
                'type' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The type of attribute (text or swatch)'
                ],
                'items' => [
                    'type' => Type::listOf($this->typeRegistry->getType('attribute')),
                    'description' => 'The attribute items in this set'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
