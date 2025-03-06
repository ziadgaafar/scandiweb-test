<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ProductType extends ObjectType
{
    public function __construct(private TypeRegistry $typeRegistry)
    {
        $config = [
            'name' => 'Product',
            'description' => 'A product in the store',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The unique identifier of the product'
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'The name of the product'
                ],
                'inStock' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'Whether the product is in stock',
                    'resolve' => function ($product) {
                        return (bool) ($product['inStock'] ?? false);
                    }
                ],
                'gallery' => [
                    'type' => Type::listOf(Type::string()),
                    'description' => 'Product images gallery',
                    'resolve' => function ($product) {
                        return $this->typeRegistry
                            ->getResolver('product')
                            ->getProductGallery($product['id']);
                    }
                ],
                'description' => [
                    'type' => Type::string(),
                    'description' => 'Product description'
                ],
                'category' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Product category name'
                ],
                'attributes' => [
                    'type' => Type::listOf($this->typeRegistry->getType('attributeSet')),
                    'description' => 'Product attributes',
                    'resolve' => function ($product) {
                        return $this->typeRegistry
                            ->getResolver('attribute')
                            ->getAttributesByProduct($product['id']);
                    }
                ],
                'prices' => [
                    'type' => Type::listOf($this->typeRegistry->getType('price')),
                    'description' => 'Product prices',
                    'resolve' => function ($product) {
                        return $this->typeRegistry
                            ->getResolver('price')
                            ->getPricesByProduct($product['id']);
                    }
                ],
                'brand' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Product brand name'
                ]
            ]
        ];

        parent::__construct($config);
    }
}
