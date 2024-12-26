<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CurrencyType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Currency',
            'description' => 'Currency information',
            'fields' => [
                'label' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Currency label (e.g., USD)'
                ],
                'symbol' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Currency symbol (e.g., $)'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
