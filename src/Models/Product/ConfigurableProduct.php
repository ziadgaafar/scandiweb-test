<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;
use App\GraphQL\Exception\InvalidAttributeException;
use App\GraphQL\Exception\MissingAttributesException;

/**
 * Configurable Product implementation
 * Represents products with selectable attributes (e.g., size, color)
 */
class ConfigurableProduct extends AbstractProduct
{
    /**
     * Get all attributes for this configurable product
     */
    public function getAttributes(): array
    {
        return $this->data['attributes'] ?? [];
    }

    /**
     * Get items for a specific attribute set
     */
    protected function getAttributeItems(string $attributeId): array
    {
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute['id'] === $attributeId) {
                return $attribute['items'] ?? [];
            }
        }

        return [];
    }

    /**
     * Validate that all required attributes are selected with valid values
     */
    public function validateAttributes(array $selectedAttributes): bool
    {
        $productAttributes = $this->getAttributes();
        $requiredAttributeIds = array_column($productAttributes, 'id');
        $selectedAttributeIds = array_column($selectedAttributes, 'id');

        // Check if all required attributes are provided
        $missingAttributes = array_diff($requiredAttributeIds, $selectedAttributeIds);
        if (!empty($missingAttributes)) {
            $missingNames = array_map(function ($attrId) use ($productAttributes) {
                foreach ($productAttributes as $attr) {
                    if ($attr['id'] === $attrId) {
                        return $attr['name'];
                    }
                }
                return $attrId;
            }, $missingAttributes);

            throw new MissingAttributesException(
                $this->getId(),
                $missingNames
            );
        }

        // Validate each selected attribute value
        foreach ($selectedAttributes as $selected) {
            $attributeItems = $this->getAttributeItems($selected['id']);
            $validValues = array_column($attributeItems, 'value');

            if (!in_array($selected['value'], $validValues)) {
                // Find attribute name for better error message
                $attrName = '';
                foreach ($productAttributes as $attr) {
                    if ($attr['id'] === $selected['id']) {
                        $attrName = $attr['name'];
                        break;
                    }
                }

                throw new InvalidAttributeException(
                    "Invalid value for attribute {$attrName}"
                );
            }
        }

        return true;
    }

    /**
     * Get product type identifier
     */
    public function getType(): string
    {
        return 'configurable';
    }

    /**
     * Override toArray to include additional configurable product data
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['__typename'] = 'ConfigurableProduct';
        return $data;
    }
}
