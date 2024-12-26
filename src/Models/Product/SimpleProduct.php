<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;

class SimpleProduct extends AbstractProduct
{
    public function getAttributes(): array
    {
        return [];
    }

    public function validateAttributes(array $selectedAttributes): bool
    {
        return empty($selectedAttributes);
    }

    public function getType(): string
    {
        return 'simple';
    }
}
