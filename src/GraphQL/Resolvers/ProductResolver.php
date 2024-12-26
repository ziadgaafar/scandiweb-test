<?php

namespace App\GraphQL\Resolvers;

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

    public function checkProductAvailability(array $products): bool
    {
        $productIds = array_column($products, 'productId');
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';

        $result = $this->executeQuery(
            "SELECT COUNT(*) as count FROM products 
            WHERE id IN ($placeholders) AND in_stock = 1",  // Changed inStock to in_stock
            $productIds
        );

        return $result[0]['count'] === count($productIds);
    }
}
