<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * SwatchAttribute class for handling color/visual attributes
 */
class SwatchAttribute extends AbstractAttribute
{
    private const HEX_COLOR_PATTERN = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    /**
     * Validate a color value
     *
     * @param mixed $value Color value to validate (hex format)
     * @return bool Whether the value is valid
     */
    public function validateValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check hex color format
        if (!preg_match(self::HEX_COLOR_PATTERN, $value)) {
            return false;
        }

        // Validate against available values
        return $this->hasValue($value);
    }

    /**
     * Validate swatch item data
     *
     * @param array $item Swatch item data
     * @return bool Whether the item is valid
     */
    public function validateItem(array $item): bool
    {
        // Required fields
        $required = ['id', 'displayValue', 'value'];
        foreach ($required as $field) {
            if (!isset($item[$field])) {
                return false;
            }
        }

        // Validate color value
        if (!preg_match(self::HEX_COLOR_PATTERN, $item['value'])) {
            return false;
        }

        // Display value should not be empty
        if (empty(trim($item['displayValue']))) {
            return false;
        }

        return true;
    }

    /**
     * Convert to GraphQL format
     *
     * @return array GraphQL-formatted swatch attribute data
     */
    public function toGraphQL(): array
    {
        $items = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'displayValue' => $item['displayValue'],
                'value' => $item['value'],
                '__typename' => 'Attribute'
            ];
        }, $this->getItems());

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'swatch',
            'items' => $items,
            '__typename' => 'AttributeSet'
        ];
    }

    /**
     * Get color value in RGB format
     *
     * @param string $hexColor Hex color value
     * @return array|null RGB values or null if invalid
     */
    public function getRGBValue(string $hexColor): ?array
    {
        if (!$this->validateValue($hexColor)) {
            return null;
        }

        // Remove # from hex color
        $hex = ltrim($hexColor, '#');

        // Convert to RGB
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
}
