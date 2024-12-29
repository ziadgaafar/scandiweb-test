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
                    'description' => 'The name of the category'
                ],
                'productCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Number of products in this category'
                ],
                'products' => [
                    'type' => Type::listOf($this->typeRegistry->getType('product')),
                    'description' => 'Products in this category',
                    'resolve' => function ($category, $args) {
                        return $this->typeRegistry
                            ->getResolver('category')
                            ->getCategoryProducts($category['name']);
                    }
                ],
                'stats' => [
                    'type' => new ObjectType([
                        'name' => 'CategoryStats',
                        'fields' => [
                            'totalProducts' => [
                                'type' => Type::nonNull(Type::int()),
                                'description' => 'Total number of products'
                            ],
                            'inStockProducts' => [
                                'type' => Type::nonNull(Type::int()),
                                'description' => 'Number of in-stock products'
                            ],
                            'outOfStockProducts' => [
                                'type' => Type::nonNull(Type::int()),
                                'description' => 'Number of out-of-stock products'
                            ]
                        ]
                    ]),
                    'description' => 'Statistical information about the category',
                    'resolve' => function ($category) {
                        return $this->typeRegistry
                            ->getResolver('category')
                            ->getCategoryStats($category['name']);
                    }
                ]
            ]
        ];

        parent::__construct($config);
    }
}
