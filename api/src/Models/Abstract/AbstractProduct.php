<?php

namespace App\Models\Abstract;

/**
 * Abstract base class for all product types
 * Handles common product properties and behaviors
 */
abstract class AbstractProduct
{
    protected array $data = [];

    /**
     * Required product properties
     */
    protected array $requiredProperties = [
        'id',
        'name',
        'brand',
        'category',
        'inStock',
        'description'
    ];

    /**
     * Initialize product with data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Set product data and validate required fields
     */
    public function setData(array $data): void
    {
        // Validate required fields
        foreach ($this->requiredProperties as $property) {
            if (!isset($data[$property])) {
                throw new \InvalidArgumentException("Missing required product property: {$property}");
            }
        }

        $this->data = $data;

        // Ensure correct data types
        $this->data['inStock'] = (bool) $this->data['inStock'];
        if (isset($this->data['prices'])) {
            foreach ($this->data['prices'] as &$price) {
                $price['amount'] = (float) $price['amount'];
            }
        }
    }

    /**
     * Get all product data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific product property
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check if the product is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->data['inStock'];
    }

    /**
     * Get product ID
     */
    public function getId(): string
    {
        return $this->data['id'];
    }

    /**
     * Get product name
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * Get formatted price for display
     */
    public function getFormattedPrice(string $currencyCode = 'USD'): ?string
    {
        if (!isset($this->data['prices'])) {
            return null;
        }

        foreach ($this->data['prices'] as $price) {
            if ($price['currency']['label'] === $currencyCode) {
                return sprintf(
                    '%s%s',
                    $price['currency']['symbol'],
                    number_format($price['amount'], 2)
                );
            }
        }

        return null;
    }

    /**
     * Get product price amount
     */
    public function getPriceAmount(string $currencyCode = 'USD'): ?float
    {
        if (!isset($this->data['prices'])) {
            return null;
        }

        foreach ($this->data['prices'] as $price) {
            if ($price['currency']['label'] === $currencyCode) {
                return $price['amount'];
            }
        }

        return null;
    }

    /**
     * Check if the product belongs to a specific category
     */
    public function isInCategory(string $category): bool
    {
        return $this->data['category'] === $category;
    }

    /**
     * Get product gallery images
     */
    public function getGallery(): array
    {
        return $this->data['gallery'] ?? [];
    }

    /**
     * Get main product image (first gallery image)
     */
    public function getMainImage(): ?string
    {
        return $this->getGallery()[0] ?? null;
    }

    /**
     * Abstract methods that must be implemented by concrete product classes
     */

    /**
     * Get all attributes for this product
     */
    abstract public function getAttributes(): array;

    /**
     * Validate selected attributes against product's available attributes
     */
    abstract public function validateAttributes(array $selectedAttributes): bool;

    /**
     * Get the product type (simple/configurable)
     */
    abstract public function getType(): string;

    /**
     * Convert product to array format for GraphQL
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'inStock' => $this->isAvailable(),
            'brand' => $this->get('brand'),
            'description' => $this->get('description'),
            'category' => $this->get('category'),
            'gallery' => $this->getGallery(),
            'prices' => $this->data['prices'] ?? [],
            'attributes' => $this->getAttributes(),
            '__typename' => 'Product'
        ];
    }
}
