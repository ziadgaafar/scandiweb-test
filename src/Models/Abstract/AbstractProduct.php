<?php

namespace App\Models\Abstract;

use App\GraphQL\Exception\ProductNotFoundException;
use App\GraphQL\Exception\ProductUnavailableException;

abstract class AbstractProduct extends AbstractModel
{
    protected string $table = 'products';
    protected array $fillable = [
        'id',
        'name',
        'in_stock',
        'description',
        'category',
        'brand'
    ];

    /**
     * Get all attributes for this product
     *
     * @return array Array of product attributes
     */
    abstract public function getAttributes(): array;

    /**
     * Validate selected attributes against product's available attributes
     *
     * @param array $selectedAttributes Array of selected attribute values
     * @return bool True if attributes are valid
     */
    abstract public function validateAttributes(array $selectedAttributes): bool;

    /**
     * Get the product type (simple/configurable)
     *
     * @return string Product type identifier
     */
    abstract public function getType(): string;

    /**
     * Get products with optional category filter
     *
     * @param array $args Query arguments
     * @return array
     */
    public function getProducts(?array $args = []): array
    {
        $query = "
            SELECT 
                p.*,
                GROUP_CONCAT(pg.image_url ORDER BY pg.position ASC SEPARATOR '|||') as gallery_images
            FROM {$this->table} p
            LEFT JOIN product_gallery pg ON p.id = pg.product_id
        ";

        $params = [];

        if (isset($args['category']) && $args['category'] !== 'all') {
            $query .= " WHERE p.category = :category";
            $params['category'] = $args['category'];
        }

        $query .= " GROUP BY p.id";

        $products = $this->executeQuery($query, $params);

        // Process gallery images
        foreach ($products as &$product) {
            $product['gallery'] = !empty($product['gallery_images']) ?
                explode('|||', $product['gallery_images']) : [];
            unset($product['gallery_images']);
        }

        return $products;
    }

    /**
     * Get product with all related data
     *
     * @param string $id
     * @return array|null
     */
    public function getProduct(string $id): ?array
    {
        $query = "
            SELECT 
                p.*
            FROM {$this->table} p
            WHERE p.id = :id
        ";

        $product = $this->executeSingle($query, ['id' => $id]);

        if ($product) {
            $this->fill($product);
            $product['gallery'] = $this->getGallery();
            $product['prices'] = $this->getPrices();
            $product['inStock'] = boolval($product['in_stock']);
        }

        return $product;
    }

    /**
     * Check if multiple products are available
     *
     * @param array $items Array of items with productId
     * @return bool
     * @throws ProductNotFoundException
     * @throws ProductUnavailableException
     */
    public function checkProductsAvailability(array $items): bool
    {
        foreach ($items as $item) {
            $product = $this->getProduct($item['productId']);

            if (!$product) {
                throw new ProductNotFoundException($item['productId']);
            }

            if (!$product['in_stock']) {
                throw new ProductUnavailableException($item['productId']);
            }
        }

        return true;
    }

    /**
     * Get product gallery images
     *
     * @return array
     */
    protected function getGallery(): array
    {
        $query = "
            SELECT image_url
            FROM product_gallery
            WHERE product_id = :productId
            ORDER BY position ASC
        ";

        return array_column(
            $this->executeQuery($query, ['productId' => $this->getId()]),
            'image_url'
        );
    }

    /**
     * Get gallery images for a specific product ID
     * This is used by the resolver for direct queries
     *
     * @param string $productId
     * @return array
     */
    public static function getProductGallery(string $productId): array
    {
        $instance = new static();
        $query = "
            SELECT image_url
            FROM product_gallery
            WHERE product_id = :productId
            ORDER BY position ASC
        ";

        $result = $instance->executeQuery($query, ['productId' => $productId]);
        return array_column($result, 'image_url');
    }

    /**
     * Get product price in specified currency
     *
     * @param string $currency Currency code (default: USD)
     * @return float Product price
     */
    public function getPrice(string $currency = 'USD'): float
    {
        $result = $this->executeSingle(
            "SELECT p.amount 
            FROM prices p 
            JOIN currencies c ON p.currency_id = c.id 
            WHERE p.product_id = :product_id 
            AND c.label = :currency",
            [
                'product_id' => $this->getId(),
                'currency' => $currency
            ]
        );

        return (float)($result['amount'] ?? 0.0);
    }

    /**
     * Get product prices with currency information
     *
     * @return array
     */
    protected function getPrices(): array
    {
        $query = "
            SELECT 
                p.amount,
                c.label,
                c.symbol
            FROM prices p
            JOIN currencies c ON p.currency_id = c.id
            WHERE p.product_id = :productId
        ";

        $prices = $this->executeQuery($query, ['productId' => $this->getId()]);

        return array_map(function ($price) {
            return [
                'amount' => (float)$price['amount'],
                'currency' => [
                    'label' => $price['label'],
                    'symbol' => $price['symbol']
                ]
            ];
        }, $prices);
    }
}
