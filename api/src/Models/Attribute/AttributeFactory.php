<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * Factory class for creating appropriate attribute instances
 */
class AttributeFactory
{
    /**
     * Create and initialize an attribute instance
     *
     * @param array $data Attribute data including type and other properties
     * @return AbstractAttribute
     * @throws \InvalidArgumentException If type is invalid
     */
    public static function create(array $data): AbstractAttribute
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException("Attribute type must be specified");
        }

        $instance = match ($data['type']) {
            'text' => new TextAttribute(),
            'swatch' => new SwatchAttribute(),
            default => throw new \InvalidArgumentException("Unknown attribute type: {$data['type']}")
        };

        $instance->setData($data);
        return $instance;
    }
}
