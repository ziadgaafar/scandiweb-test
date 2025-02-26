<?php

namespace App\Repositories;

use PDO;
use App\Services\Database\MySQLConnection;

/**
 * Repository class for handling all attribute-related database operations
 */
class AttributeRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    /**
     * Get all attribute sets with their items
     *
     * @return array Array of attribute sets with their items
     * @throws \RuntimeException If database operation fails
     */
    public function getAllAttributeSets(): array
    {
        try {
            // Get all attribute sets
            $setsQuery = "
                SELECT 
                    attr_sets.id as id,
                    attr_sets.name as name,
                    attr_sets.type as type
                FROM attribute_sets attr_sets
                ORDER BY attr_sets.name
            ";

            $stmt = $this->connection->query($setsQuery);
            $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get items for each set
            $itemsQuery = "
                SELECT 
                    id,
                    display_value as displayValue,
                    value
                FROM attribute_items
                WHERE attribute_set_id = :setId
                ORDER BY id
            ";

            $itemStmt = $this->connection->prepare($itemsQuery);
            $result = [];

            foreach ($sets as $set) {
                $itemStmt->execute(['setId' => $set['id']]);
                $result[] = [
                    'id' => $set['id'],
                    'name' => $set['name'],
                    'type' => $set['type'],
                    'items' => $itemStmt->fetchAll(PDO::FETCH_ASSOC)
                ];
            }

            return $result;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching attribute sets: " . $e->getMessage());
        }
    }

    /**
     * Get attribute set by ID with its items
     *
     * @param string $id Attribute set ID
     * @return array|null Attribute set data with items or null if not found
     * @throws \RuntimeException If database operation fails
     */
    public function getAttributeSetById(string $id): ?array
    {
        try {
            $query = "
                SELECT 
                    attr_sets.id as id,
                    attr_sets.name as name,
                    attr_sets.type as type
                FROM attribute_sets attr_sets
                WHERE attr_sets.id = :id
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(['id' => $id]);
            $set = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$set) {
                return null;
            }

            // Get items for this set
            $itemsQuery = "
                SELECT 
                    id,
                    display_value as displayValue,
                    value
                FROM attribute_items
                WHERE attribute_set_id = :setId
                ORDER BY id
            ";

            $itemStmt = $this->connection->prepare($itemsQuery);
            $itemStmt->execute(['setId' => $id]);

            // Ensure all required properties are present and correctly named
            return [
                'id' => $set['id'],
                'name' => $set['name'],
                'type' => $set['type'],
                'items' => $itemStmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching attribute set: " . $e->getMessage());
        }
    }

    /**
     * Get attributes for a specific product
     *
     * @param string $productId Product ID
     * @return array Array of attribute sets with their items for the product
     * @throws \RuntimeException If database operation fails
     */
    public function getAttributesByProduct(string $productId): array
    {
        try {
            // First get the attribute sets
            $setsQuery = "
                SELECT DISTINCT
                    attr_sets.id as id,
                    attr_sets.name as name,
                    attr_sets.type as type
                FROM attribute_sets attr_sets
                INNER JOIN product_attributes pa ON pa.attribute_set_id = attr_sets.id
                WHERE pa.product_id = :productId
            ";

            $stmt = $this->connection->prepare($setsQuery);
            $stmt->execute(['productId' => $productId]);
            $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Then get items for each set
            $itemsQuery = "
                SELECT 
                    id,
                    display_value as displayValue,
                    value
                FROM attribute_items
                WHERE attribute_set_id = :setId
                ORDER BY id
            ";

            $itemStmt = $this->connection->prepare($itemsQuery);
            $result = [];

            // Combine sets with their items
            foreach ($sets as $set) {
                $itemStmt->execute(['setId' => $set['id']]);
                $result[] = [
                    'id' => $set['id'],
                    'name' => $set['name'],
                    'type' => $set['type'],
                    'items' => $itemStmt->fetchAll(PDO::FETCH_ASSOC)
                ];
            }

            return $result;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product attributes: " . $e->getMessage());
        }
    }

    /**
     * Validate product attributes against available options
     *
     * @param string $productId Product ID
     * @param array $selectedAttributes Array of selected attributes
     * @return bool True if all attributes are valid
     * @throws \RuntimeException If database operation fails
     */
    public function validateProductAttributes(string $productId, array $selectedAttributes): bool
    {
        try {
            // Get valid attribute values for the product
            $query = "
                SELECT 
                    attr_sets.id as set_id,
                    items.value
                FROM attribute_sets attr_sets
                INNER JOIN product_attributes pa ON pa.attribute_set_id = attr_sets.id
                INNER JOIN attribute_items items ON items.attribute_set_id = attr_sets.id
                WHERE pa.product_id = :productId
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(['productId' => $productId]);
            $validAttributes = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

            // Check each selected attribute
            foreach ($selectedAttributes as $selected) {
                if (
                    !isset($validAttributes[$selected['id']]) ||
                    !in_array($selected['value'], $validAttributes[$selected['id']])
                ) {
                    return false;
                }
            }

            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error validating attributes: " . $e->getMessage());
        }
    }

    /**
     * Create a new attribute set
     *
     * @param array $data Attribute set data
     * @return string|null Created attribute set ID or null on failure
     * @throws \RuntimeException If database operation fails
     */
    public function createAttributeSet(array $data): ?string
    {
        try {
            $this->connection->beginTransaction();

            // Create attribute set
            $stmt = $this->connection->prepare("
                INSERT INTO attribute_sets (id, name, type)
                VALUES (:id, :name, :type)
            ");

            $stmt->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'type' => $data['type']
            ]);

            // Create attribute items if provided
            if (!empty($data['items'])) {
                $itemStmt = $this->connection->prepare("
                    INSERT INTO attribute_items 
                    (id, attribute_set_id, display_value, value)
                    VALUES (:id, :setId, :displayValue, :value)
                ");

                foreach ($data['items'] as $item) {
                    $itemStmt->execute([
                        'id' => $item['id'],
                        'setId' => $data['id'],
                        'displayValue' => $item['displayValue'],
                        'value' => $item['value']
                    ]);
                }
            }

            $this->connection->commit();
            return $data['id'];
        } catch (\PDOException $e) {
            $this->connection->rollBack();
            throw new \RuntimeException("Error creating attribute set: " . $e->getMessage());
        }
    }

    /**
     * Update an attribute set
     *
     * @param string $id Attribute set ID
     * @param array $data Updated attribute set data
     * @return bool Success status
     * @throws \RuntimeException If database operation fails
     */
    public function updateAttributeSet(string $id, array $data): bool
    {
        try {
            $this->connection->beginTransaction();

            // Update attribute set
            $stmt = $this->connection->prepare("
                UPDATE attribute_sets 
                SET name = :name, type = :type
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'type' => $data['type']
            ]);

            // Update items if provided
            if (isset($data['items'])) {
                // Remove existing items
                $this->connection->prepare("
                    DELETE FROM attribute_items 
                    WHERE attribute_set_id = :setId
                ")->execute(['setId' => $id]);

                // Insert new items
                $itemStmt = $this->connection->prepare("
                    INSERT INTO attribute_items 
                    (id, attribute_set_id, display_value, value)
                    VALUES (:id, :setId, :displayValue, :value)
                ");

                foreach ($data['items'] as $item) {
                    $itemStmt->execute([
                        'id' => $item['id'],
                        'setId' => $id,
                        'displayValue' => $item['displayValue'],
                        'value' => $item['value']
                    ]);
                }
            }

            $this->connection->commit();
            return true;
        } catch (\PDOException $e) {
            $this->connection->rollBack();
            throw new \RuntimeException("Error updating attribute set: " . $e->getMessage());
        }
    }

    /**
     * Delete an attribute set
     *
     * @param string $id Attribute set ID
     * @return bool Success status
     * @throws \RuntimeException If database operation fails
     */
    public function deleteAttributeSet(string $id): bool
    {
        try {
            $stmt = $this->connection->prepare("
                DELETE FROM attribute_sets WHERE id = :id
            ");
            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error deleting attribute set: " . $e->getMessage());
        }
    }
}
