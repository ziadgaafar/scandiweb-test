<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * Text attribute implementation (e.g., size, capacity)
 */
class TextAttribute extends AbstractAttribute
{
    public function validateValue($value): bool
    {
        return is_string($value) && !empty(trim($value));
    }

    public function toGraphQL(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'text',
            'items' => $this->getItems(),
            '__typename' => 'AttributeSet'
        ];
    }
}
