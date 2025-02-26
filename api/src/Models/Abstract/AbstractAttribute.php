<?php

namespace App\Models\Abstract;

/**
 * Abstract base class for all attribute types
 */
abstract class AbstractAttribute
{
    protected array $data = [];

    /**
     * Initialize attribute
     */
    public function __construct()
    {
        // Empty constructor - data will be set via setData
    }

    /**
     * Set attribute data and validate
     *
     * @param array $data Attribute data
     * @throws \InvalidArgumentException If required data is missing
     */
    public function setData(array $data): void
    {
        // Validate required fields
        $requiredFields = ['id', 'name', 'type', 'items'];
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $fields = implode(', ', $missing);
            throw new \InvalidArgumentException("Missing required attribute properties: {$fields}");
        }

        $this->data = $data;
    }

    /**
     * Get attribute ID
     */
    public function getId(): string
    {
        return $this->data['id'];
    }

    /**
     * Get attribute name
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * Get attribute type
     */
    public function getType(): string
    {
        return $this->data['type'];
    }

    /**
     * Get attribute items
     */
    public function getItems(): array
    {
        return $this->data['items'] ?? [];
    }

    /**
     * Convert to GraphQL format
     */
    public function toGraphQL(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'displayValue' => $item['displayValue'],
                    'value' => $item['value'],
                    '__typename' => 'Attribute'
                ];
            }, $this->getItems()),
            '__typename' => 'AttributeSet'
        ];
    }

    /**
     * Check if a value exists in attribute items
     */
    protected function hasValue(string $value): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item['value'] === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get display value for a given value
     */
    public function getDisplayValue(string $value): ?string
    {
        foreach ($this->getItems() as $item) {
            if ($item['value'] === $value) {
                return $item['displayValue'];
            }
        }
        return null;
    }

    /**
     * Validate a value against attribute rules
     */
    abstract public function validateValue($value): bool;
}
