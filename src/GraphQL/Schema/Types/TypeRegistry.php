<?php

namespace App\GraphQL\Schema\Types;

use GraphQL\Type\Definition\Type;
use App\GraphQL\Resolvers\CategoryResolver;
use App\GraphQL\Resolvers\ProductResolver;
use App\GraphQL\Resolvers\OrderResolver;
use App\GraphQL\Resolvers\AttributeResolver;
use App\GraphQL\Resolvers\PriceResolver;

class TypeRegistry
{
    private static ?TypeRegistry $instance = null;
    private array $types = [];
    private array $resolvers = [];
    private array $typeInstances = [];

    private function __construct()
    {
        $this->initializeResolvers();
        // Initialize type definitions (but don't create instances yet)
        $this->initializeTypeDefinitions();
    }

    private function initializeTypeDefinitions(): void
    {
        $this->types = [
            'category' => fn() => new CategoryType($this),
            'product' => fn() => new ProductType($this),
            'attribute' => fn() => new AttributeType($this),
            'attributeSet' => fn() => new AttributeSetType($this),
            'price' => fn() => new PriceType($this),
            'currency' => fn() => new CurrencyType($this),
            'order' => fn() => new OrderType($this),
            'orderItem' => fn() => new OrderItemType($this),
            'selectedAttribute' => fn() => new SelectedAttributeType($this),
            'orderInput' => fn() => new OrderInputType($this),
            'orderItemInput' => fn() => new OrderItemInputType($this),
            'selectedAttributeInput' => fn() => new SelectedAttributeInputType($this)
        ];
    }

    private function initializeResolvers(): void
    {
        $this->resolvers = [
            'category' => new CategoryResolver(),
            'product' => new ProductResolver(),
            'order' => new OrderResolver(),
            'attribute' => new AttributeResolver(),
            'price' => new PriceResolver()
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getType(string $name): Type
    {

        if (!isset($this->types[$name])) {
            throw new \RuntimeException("Type {$name} not found in registry");
        }

        if (!isset($this->typeInstances[$name])) {
            $this->typeInstances[$name] = $this->types[$name]();
        }

        return $this->typeInstances[$name];
    }

    public function getResolver(string $name): object
    {
        if (!isset($this->resolvers[$name])) {
            throw new \RuntimeException("Resolver {$name} not found in registry");
        }
        return $this->resolvers[$name];
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
