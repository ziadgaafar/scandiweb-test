<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * Swatch attribute implementation (e.g., colors)
 */
class SwatchAttribute extends AbstractAttribute
{
    public function validateValue($value): bool
    {
        return preg_match('/^#[0-9A-F]{6}$/i', $value);
    }

    public function toGraphQL(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'swatch',
            'items' => $this->getItems(),
            '__typename' => 'AttributeSet'
        ];
    }
}
