<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;
use App\GraphQL\Exception\InvalidAttributeException;

/**
 * Simple Product implementation
 * Represents products without configurable attributes
 */
class SimpleProduct extends AbstractProduct
{
    /**
     * Simple products have no configurable attributes
     */
    public function getAttributes(): array
    {
        return [];
    }

    /**
     * Simple products don't require attribute validation
     * Always returns true as simple products have no attributes to validate
     */
    public function validateAttributes(array $selectedAttributes): bool
    {
        // Simple products shouldn't have any attributes selected
        if (!empty($selectedAttributes)) {
            throw new InvalidAttributeException(
                "Simple product cannot have attributes selected"
            );
        }

        return true;
    }

    /**
     * Get product type identifier
     */
    public function getType(): string
    {
        return 'simple';
    }
}
