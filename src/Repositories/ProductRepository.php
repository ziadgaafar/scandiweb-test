<?php

namespace App\Repositories;

use PDO;
use App\Services\Database\MySQLConnection;
use App\GraphQL\Exception\ProductNotFoundException;

class ProductRepository
{
    private PDO $connection;
    private string $table = 'products';

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    /**
     * Get all products with optional category filter
     *
     * @param array|null $args Filter arguments
     * @return array Array of products
     */
    public function getAll(?array $args = []): array
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

        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process gallery images
            foreach ($products as &$product) {
                $product['gallery'] = !empty($product['gallery_images'])
                    ? explode('|||', $product['gallery_images'])
                    : [];
                unset($product['gallery_images']);
            }

            return $products;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching products: " . $e->getMessage());
        }
    }

    /**
     * Get a single product by ID with all related data
     *
     * @param string $id Product ID
     * @return array Product data
     * @throws ProductNotFoundException
     */
    public function getById(string $id): array
    {
        try {
            // Get base product data
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(['id' => $id]);

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new ProductNotFoundException($id);
            }

            // Enrich with related data
            $product['gallery'] = $this->getProductGallery($id);
            $product['prices'] = $this->getProductPrices($id);
            $product['attributes'] = $this->getProductAttributes($id);
            $product['inStock'] = (bool)$product['in_stock'];

            return $product;
        } catch (ProductNotFoundException $e) {
            throw $e;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product: " . $e->getMessage());
        }
    }

    /**
     * Get product gallery images
     *
     * @param string $productId
     * @return array
     */
    public function getProductGallery(string $productId): array
    {
        $query = "
            SELECT image_url
            FROM product_gallery
            WHERE product_id = :productId
            ORDER BY position ASC
        ";

        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute(['productId' => $productId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'image_url');
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product gallery: " . $e->getMessage());
        }
    }

    /**
     * Get product prices with currency information
     *
     * @param string $productId
     * @return array
     */
    public function getProductPrices(string $productId): array
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

        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute(['productId' => $productId]);
            $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($price) {
                return [
                    'amount' => (float)$price['amount'],
                    'currency' => [
                        'label' => $price['label'],
                        'symbol' => $price['symbol']
                    ]
                ];
            }, $prices);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product prices: " . $e->getMessage());
        }
    }

    /**
     * Get product attributes with their items
     *
     * @param string $productId
     * @return array
     */
    public function getProductAttributes(string $productId): array
    {
        try {
            // First get attribute sets
            $query = "
                SELECT DISTINCT
                    attr_sets.id,
                    attr_sets.name,
                    attr_sets.type
                FROM attribute_sets attr_sets
                INNER JOIN product_attributes pa ON pa.attribute_set_id = attr_sets.id
                WHERE pa.product_id = :productId
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(['productId' => $productId]);
            $attributeSets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($attributeSets)) {
                return [];
            }

            // Then get items for each attribute set
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

                $stmt = $this->connection->prepare($itemsQuery);
                $stmt->execute(['setId' => $set['id']]);
                $set['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $attributeSets;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product attributes: " . $e->getMessage());
        }
    }

    /**
     * Check if multiple products are available
     *
     * @param array $items Array of items with productId
     * @return bool
     * @throws ProductNotFoundException
     */
    public function checkProductsAvailability(array $items): bool
    {
        $productIds = array_column($items, 'productId');

        try {
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $query = "
                SELECT id, in_stock 
                FROM {$this->table} 
                WHERE id IN ({$placeholders})
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($items as $item) {
                if (!isset($products[$item['productId']])) {
                    throw new ProductNotFoundException($item['productId']);
                }

                if (!$products[$item['productId']]) {
                    return false;
                }
            }

            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error checking product availability: " . $e->getMessage());
        }
    }
}
