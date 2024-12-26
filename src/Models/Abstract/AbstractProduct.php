<?php

namespace App\Models\Abstract;

abstract class AbstractProduct extends AbstractModel
{
    protected int $id;
    protected string $name;
    protected bool $inStock;
    protected array $gallery;
    protected string $description;
    protected string $category;
    protected string $brand;
    protected array $prices;

    abstract public function getAttributes(): array;
    abstract public function validateAttributes(array $selectedAttributes): bool;
    abstract public function getType(): string;

    public function getPrice(string $currency = 'USD'): float
    {
        return array_filter(
            $this->prices,
            fn($price) =>
            $price['currency']['label'] === $currency
        )[0]['amount'] ?? 0.0;
    }
}
