<?php

namespace App\Models\Attribute;

use App\Models\Abstract\AbstractAttribute;

/**
 * TextAttribute class for handling text-based attributes (size, capacity, etc.)
 */
class TextAttribute extends AbstractAttribute
{
    private const MAX_VALUE_LENGTH = 191; // Matches database column length

    /**
     * Validate a text value
     *
     * @param mixed $value Text value to validate
     * @return bool Whether the value is valid
     */
    public function validateValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check value length
        $length = strlen($value);
        if ($length === 0 || $length > self::MAX_VALUE_LENGTH) {
            return false;
        }

        // Validate against available values
        return $this->hasValue($value);
    }

    /**
     * Validate text attribute item data
     *
     * @param array $item Text attribute item data
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

        // Validate value length
        if (strlen($item['value']) > self::MAX_VALUE_LENGTH) {
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
     * @return array GraphQL-formatted text attribute data
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
            'type' => 'text',
            'items' => $items,
            '__typename' => 'AttributeSet'
        ];
    }

    /**
     * Format display value based on attribute type
     * (e.g., size might need special formatting)
     *
     * @param string $value Raw value
     * @return string Formatted value
     */
    public function formatDisplayValue(string $value): string
    {
        // Get display value if it exists
        $displayValue = $this->getDisplayValue($value);
        if ($displayValue !== null) {
            return $displayValue;
        }

        // Default formatting based on attribute name
        switch (strtolower($this->getName())) {
            case 'size':
                return strtoupper($value);
            case 'capacity':
                return preg_match('/^\d+$/', $value) ? "{$value}GB" : $value;
            default:
                return $value;
        }
    }
}
