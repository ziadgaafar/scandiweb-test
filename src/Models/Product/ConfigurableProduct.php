<?php

namespace App\Models\Product;

use App\Models\Abstract\AbstractProduct;
use App\GraphQL\Exception\InvalidAttributeException;

class ConfigurableProduct extends AbstractProduct
{
    /**
     * Get all attributes for this configurable product
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->executeQuery(
            "SELECT 
                attr_sets.id,
                attr_sets.name,
                attr_sets.type
            FROM attribute_sets attr_sets
            INNER JOIN product_attributes pa ON pa.attribute_set_id = attr_sets.id
            WHERE pa.product_id = :productId",
            ['productId' => $this->getId()]
        );
    }

    /**
     * Get attribute items for a specific attribute set
     *
     * @param string $attributeSetId
     * @return array
     */
    protected function getAttributeItems(string $attributeSetId): array
    {
        return $this->executeQuery(
            "SELECT 
                id,
                display_value as displayValue,
                value
            FROM attribute_items
            WHERE attribute_set_id = :setId
            ORDER BY id",
            ['setId' => $attributeSetId]
        );
    }

    /**
     * Validate selected attributes against product's configuration
     *
     * @param array $selectedAttributes
     * @return bool
     * @throws InvalidAttributeException
     */
    public function validateAttributes(array $selectedAttributes): bool
    {
        $productAttributes = $this->getAttributes();

        // Check all required attributes are provided
        foreach ($productAttributes as $attribute) {
            if (!isset($selectedAttributes[$attribute['id']])) {
                throw new InvalidAttributeException(
                    "Missing required attribute: {$attribute['name']}"
                );
            }

            $validValues = array_column(
                $this->getAttributeItems($attribute['id']),
                'value'
            );

            if (!in_array($selectedAttributes[$attribute['id']], $validValues)) {
                throw new InvalidAttributeException(
                    "Invalid value for attribute {$attribute['name']}"
                );
            }
        }

        return true;
    }

    public function getType(): string
    {
        return 'configurable';
    }
}
