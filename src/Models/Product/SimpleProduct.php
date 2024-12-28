<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;

class SimpleProduct extends AbstractProduct
{
    /**
     * Simple products have no configurable attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * Simple products don't require attribute validation
     *
     * @param array $selectedAttributes
     * @return bool
     */
    public function validateAttributes(array $selectedAttributes): bool
    {
        return empty($selectedAttributes);
    }

    public function getType(): string
    {
        return 'simple';
    }
}
