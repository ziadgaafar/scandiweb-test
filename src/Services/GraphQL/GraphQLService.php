<?php

namespace App\Services\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL as GraphQLBase;
use App\GraphQL\Schema\Types\TypeRegistry;
use App\GraphQL\Schema\Mutations;
use App\GraphQL\Error\ErrorHandler;
use GraphQL\Error\Error;

class GraphQLService
{
    private static ?GraphQLService $instance = null;
    private Schema $schema;
    private TypeRegistry $typeRegistry;

    private function __construct()
    {
        $this->typeRegistry = TypeRegistry::getInstance();
        $this->initializeSchema();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeSchema(): void
    {
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'order' => [
                    'type' => $this->typeRegistry->getType('order'),
                    'args' => [
                        'id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('order')->getOrder($args['id']);
                    }
                ],
                'categories' => [
                    'type' => Type::listOf($this->typeRegistry->getType('category')),
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('category')->getCategories();
                    }
                ],
                'category' => [
                    'type' => $this->typeRegistry->getType('category'),
                    'args' => [
                        'name' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('category')->getCategory($args['name']);
                    }
                ],
                'topCategories' => [
                    'type' => Type::listOf($this->typeRegistry->getType('category')),
                    'args' => ['limit' => Type::int()],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('category')->getTopCategories();
                    }
                ],
                'products' => [
                    'type' => Type::listOf($this->typeRegistry->getType('product')),
                    'args' => [
                        'category' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('product')->getProducts($args);
                    }
                ],
                'product' => [
                    'type' => $this->typeRegistry->getType('product'),
                    'args' => [
                        'id' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('product')->getProduct($args['id']);
                    }
                ],
                'attributeSet' => [
                    'type' => $this->typeRegistry->getType('attributeSet'),
                    'args' => [
                        'id' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function ($root, $args) {
                        return $this->typeRegistry->getResolver('attribute')->getAttributeSet($args['id']);
                    }
                ],
            ]
        ]);

        $orderMutation = new Mutations\OrderMutation($this->typeRegistry);

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => array_merge(
                $orderMutation->getMutations(),
            )
        ]);

        $this->schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType
        ]);
    }

    public function executeQuery(string $query, ?array $variables = null): array
    {
        try {
            $result = GraphQLBase::executeQuery(
                $this->schema,
                $query,
                null,
                null,
                $variables
            );

            // If there are errors, format them using our ErrorHandler
            if (!empty($result->errors)) {
                foreach ($result->errors as $error) {
                    error_log("GraphQL Error: " . $error->getMessage());
                }

                return [
                    'errors' => array_map(
                        [ErrorHandler::class, 'formatError'],
                        $result->errors
                    ),
                    'data' => $result->data,
                ];
            }
            return $result->toArray();
        } catch (Error $e) {
            error_log("GraphQL Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return [
                'errors' => [
                    ErrorHandler::formatError($e)
                ]
            ];
        } catch (\Exception $e) {
            error_log("Unexpected Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            // Use our GraphQLException format for consistency
            return [
                'errors' => [
                    [
                        'message' => 'An unexpected error occurred',
                        'extensions' => [
                            'category' => 'internal',
                            'code' => 'INTERNAL_ERROR'
                        ]
                    ]
                ]
            ];
        }
    }
}
