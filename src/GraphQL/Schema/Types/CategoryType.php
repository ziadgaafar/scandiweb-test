<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CategoryType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'Category',
            'description' => 'A product category',
            'fields' => [
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The name of the category',
                ],
                'products' => [
                    'type' => Type::listOf($this->typeRegistry->getType('product')),
                    'description' => 'Products in this category',
                    'resolve' => function ($category, $args) {
                        return $this->typeRegistry
                            ->getResolver('product')
                            ->getProductsByCategory($category['name']);
                    }
                ]
            ]
        ];

        parent::__construct($config);
    }
}
