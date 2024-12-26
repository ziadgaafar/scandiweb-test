<?php

namespace App\GraphQL\Resolvers;

use App\GraphQL\Exception\ProductNotFoundException;
use App\GraphQL\Exception\ProductUnavailableException;

class ProductResolver extends AbstractResolver
{
    public function getProducts(?array $args = []): array
    {
        $query = "
            SELECT 
                p.*,
                GROUP_CONCAT(pg.image_url ORDER BY pg.position ASC) as gallery_images
            FROM products p
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
            $product['gallery'] = $product['gallery_images'] ?
                explode(',', $product['gallery_images']) : [];
            unset($product['gallery_images']);
        }

        return $products;
    }

    public function getProductsByCategory(string $category): array
    {
        if ($category === 'all') {
            return $this->getProducts();
        }

        return $this->getProducts(['category' => $category]);
    }

    public function getProduct(string $id): ?array
    {
        $query = "
            SELECT 
                p.*,
                GROUP_CONCAT(pg.image_url ORDER BY pg.position ASC) as gallery_images
            FROM products p
            LEFT JOIN product_gallery pg ON p.id = pg.product_id
            WHERE p.id = :id
            GROUP BY p.id
        ";

        $product = $this->executeSingle($query, ['id' => $id]);

        if ($product) {
            $product['gallery'] = $product['gallery_images'] ?
                explode(',', $product['gallery_images']) : [];
            unset($product['gallery_images']);
        }

        return $product;
    }

    public function getProductGallery(string $productId): array
    {
        $query = "
            SELECT image_url
            FROM product_gallery
            WHERE product_id = :productId
            ORDER BY position ASC
        ";

        $result = $this->executeQuery($query, ['productId' => $productId]);
        return array_column($result, 'image_url');
    }

    public function checkProductAvailability(array $items): bool
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
}
