<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * Factory class for creating appropriate attribute instances
 */
class AttributeFactory
{
    public static function create(string $type): AbstractAttribute
    {
        return match ($type) {
            'text' => new TextAttribute(),
            'swatch' => new SwatchAttribute(),
            default => throw new \InvalidArgumentException("Unknown attribute type: {$type}")
        };
    }
}
