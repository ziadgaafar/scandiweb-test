<?php

namespace App\GraphQL\Schema\Mutations;

use GraphQL\Type\Definition\Type;
use App\GraphQL\Schema\Types\TypeRegistry;
use App\GraphQL\Resolvers\OrderResolver;

class OrderMutation
{
    private TypeRegistry $typeRegistry;
    private OrderResolver $orderResolver;

    public function __construct(TypeRegistry $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
        $this->orderResolver = new OrderResolver();
    }

    public function getMutations(): array
    {
        return [
            'createOrder' => [
                'type' => $this->typeRegistry->getType('order'),
                'args' => [
                    'items' => Type::nonNull(Type::listOf(
                        $this->typeRegistry->getType('orderItemInput')
                    ))
                ],
                'resolve' => function ($rootValue, array $args): ?array {
                    return $this->orderResolver->createOrder($args['items']);
                }
            ],
        ];
    }
}
