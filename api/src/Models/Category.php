<?php

namespace App\Models;

use App\Models\Abstract\AbstractModel;

/**
 * Category Model
 * 
 * Represents a product category in the system.
 * Handles category-specific domain logic and validation.
 */
class Category extends AbstractModel
{
    /**
     * @var array Fillable properties for the category
     */
    protected array $fillable = [
        'name',
        'product_count'
    ];

    /**
     * Category name validation rules
     */
    private const NAME_MIN_LENGTH = 2;
    private const NAME_MAX_LENGTH = 191; // Matches database column length
    private const NAME_PATTERN = '/^[a-zA-Z0-9\s\-_]+$/'; // Alphanumeric with spaces, hyphens, and underscores

    /**
     * Validate category name
     *
     * @param string $name Category name to validate
     * @return bool Whether the name is valid
     * @throws \InvalidArgumentException if name is invalid
     */
    public function validateName(string $name): bool
    {
        $name = trim($name);
        $length = strlen($name);

        if ($length < self::NAME_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                "Category name must be at least " . self::NAME_MIN_LENGTH . " characters long"
            );
        }

        if ($length > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                "Category name cannot exceed " . self::NAME_MAX_LENGTH . " characters"
            );
        }

        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException(
                "Category name can only contain letters, numbers, spaces, hyphens, and underscores"
            );
        }

        return true;
    }

    /**
     * Get category name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Set category name
     *
     * @param string $name
     * @return self
     * @throws \InvalidArgumentException if name is invalid
     */
    public function setName(string $name): self
    {
        $name = trim($name);
        $this->validateName($name);
        $this->setAttribute('name', strtolower($name)); // Store names in lowercase for consistency
        return $this;
    }

    /**
     * Get product count
     *
     * @return int
     */
    public function getProductCount(): int
    {
        return (int) ($this->getAttribute('product_count') ?? 0);
    }

    /**
     * Set product count
     *
     * @param int $count
     * @return self
     */
    public function setProductCount(int $count): self
    {
        $this->setAttribute('product_count', max(0, $count)); // Ensure count is not negative
        return $this;
    }

    /**
     * Convert category to array for GraphQL response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'productCount' => $this->getProductCount(),
            '__typename' => 'Category'
        ];
    }

    /**
     * Create a new Category instance from array data
     *
     * @param array $data Category data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $category = new self();

        if (isset($data['name'])) {
            $category->setName($data['name']);
        }

        if (isset($data['product_count'])) {
            $category->setProductCount($data['product_count']);
        }

        return $category;
    }

    /**
     * Check if this category has any products
     *
     * @return bool
     */
    public function hasProducts(): bool
    {
        return $this->getProductCount() > 0;
    }

    /**
     * Format category name for URLs (slug)
     *
     * @return string
     */
    public function getSlug(): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $this->getName()));
    }
}
