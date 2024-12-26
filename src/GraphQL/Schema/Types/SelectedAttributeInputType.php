<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class SelectedAttributeInputType extends InputObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'SelectedAttributeInput',
            'description' => 'Input for a selected attribute value',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The attribute identifier'
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
