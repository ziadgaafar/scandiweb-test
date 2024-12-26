<?php

namespace App\GraphQL\Resolvers;

class AttributeResolver extends AbstractResolver
{
    public function getAttributesByProduct(string $productId): ?array
    {
        try {
            // First verify if the product has any attributes
            $checkQuery = "
                SELECT COUNT(*) as count
                FROM product_attributes
                WHERE product_id = :productId
            ";

            $hasAttributes = $this->executeSingle($checkQuery, ['productId' => $productId]);

            if (!$hasAttributes || $hasAttributes['count'] == 0) {
                return null;
            }

            // Get the attribute sets - Note the changed alias from 'as' to 'attr_sets'
            $query = "
                SELECT DISTINCT
                    attr_sets.id,
                    attr_sets.name,
                    attr_sets.type
                FROM attribute_sets attr_sets
                INNER JOIN product_attributes pa ON pa.attribute_set_id = attr_sets.id
                WHERE pa.product_id = :productId
            ";

            $attributeSets = $this->executeQuery($query, ['productId' => $productId]);

            if (empty($attributeSets)) {
                return null;
            }

            // For each attribute set, get its items
            foreach ($attributeSets as &$set) {
                $itemsQuery = "
                    SELECT 
                        ai.id,
                        ai.display_value as displayValue,
                        ai.value
                    FROM attribute_items ai
                    WHERE ai.attribute_set_id = :setId
                    ORDER BY ai.id
                ";

                $set['items'] = $this->executeQuery($itemsQuery, ['setId' => $set['id']]);
            }

            return $attributeSets;
        } catch (\Exception $e) {
            // Log the error with more detail
            error_log("Error fetching attributes for product $productId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            if ($e instanceof \PDOException) {
                error_log("Database error code: " . $e->getCode());
                error_log("Database error info: " . print_r($this->db->errorInfo(), true));
            }

            return null;
        }
    }

    public function validateAttributeValues(string $productId, array $selectedAttributes): bool
    {
        try {
            // Get all valid attributes for the product
            $validAttributes = $this->getAttributesByProduct($productId);

            if (!$validAttributes) {
                return false;
            }

            // Create a map of valid values for each attribute set
            $validValues = [];
            foreach ($validAttributes as $attrSet) {
                $validValues[$attrSet['id']] = array_column($attrSet['items'], 'value');
            }

            // Check each selected attribute
            foreach ($selectedAttributes as $selected) {
                // Check if the attribute exists for this product
                if (!isset($validValues[$selected['id']])) {
                    return false;
                }

                // Check if the selected value is valid for this attribute
                if (!in_array($selected['value'], $validValues[$selected['id']])) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error validating attributes for product $productId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            if ($e instanceof \PDOException) {
                error_log("Database error code: " . $e->getCode());
                error_log("Database error info: " . print_r($this->db->errorInfo(), true));
            }

            return false;
        }
    }

    public function getAttributeSet(string $attributeId): ?array
    {
        try {
            return $this->executeSingle(
                "SELECT * FROM attribute_sets WHERE id = :id",
                ['id' => $attributeId]
            );
        } catch (\Exception $e) {
            error_log("Error fetching attribute set: " . $e->getMessage());
            return null;
        }
    }
}
