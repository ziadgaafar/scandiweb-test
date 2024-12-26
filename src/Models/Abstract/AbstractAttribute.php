<?php

namespace App\Models\Abstract;

/**
 * Abstract base class for all attribute types
 */
abstract class AbstractAttribute extends AbstractModel
{
    protected string $table = 'attributes';
    protected array $fillable = ['name', 'type', 'display_value', 'value'];

    /**
     * Get all items for this attribute
     */
    public function getItems(): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM attribute_items WHERE attribute_id = :attribute_id"
        );

        $stmt->execute(['attribute_id' => $this->getId()]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get attribute items for a specific product
     */
    public function getItemsForProduct(int $productId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT ai.* 
            FROM attribute_items ai
            JOIN product_attributes pa ON ai.id = pa.attribute_item_id
            WHERE pa.product_id = :product_id 
            AND ai.attribute_id = :attribute_id"
        );

        $stmt->execute([
            'product_id' => $productId,
            'attribute_id' => $this->getId()
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    abstract public function validateValue($value): bool;
    abstract public function toGraphQL(): array;
}
