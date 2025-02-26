<?php

namespace App\GraphQL\Resolvers;

use App\Models\Product\ProductFactory;
use App\Repositories\ProductRepository;
use App\GraphQL\Exception\ProductNotFoundException;

class ProductResolver
{
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->productRepository = new ProductRepository();
    }

    /**
     * Get all products with optional category filter
     */
    public function getProducts(?array $args = []): array
    {
        try {
            $productsData = $this->productRepository->getAll($args);

            // Convert each product data to its appropriate model instance
            return array_map(function ($productData) {
                return ProductFactory::create($productData)->toArray();
            }, $productsData);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching products: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory(string $category): array
    {
        try {
            return $this->getProducts(['category' => $category]);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching products by category: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get single product by ID
     */
    public function getProduct(string $id): ?array
    {
        try {
            $productData = $this->productRepository->getById($id);

            return ProductFactory::create($productData)->toArray();
        } catch (ProductNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching product: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get product gallery images
     */
    public function getProductGallery(string $productId): array
    {
        try {
            return $this->productRepository->getProductGallery($productId);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching product gallery: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check availability of multiple products
     */
    public function checkProductAvailability(array $items): bool
    {
        try {
            return $this->productRepository->checkProductsAvailability($items);
        } catch (ProductNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error checking product availability: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
