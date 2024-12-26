<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;

class ConfigurableProduct extends AbstractProduct
{
    protected array $attributes = [];

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function validateAttributes(array $selectedAttributes): bool
    {
        foreach ($this->attributes as $attribute) {
            if (!isset($selectedAttributes[$attribute['id']])) {
                return false;
            }

            $validValues = array_column($attribute['items'], 'id');
            if (!in_array($selectedAttributes[$attribute['id']], $validValues)) {
                return false;
            }
        }
        return true;
    }

    public function getType(): string
    {
        return 'configurable';
    }
}
