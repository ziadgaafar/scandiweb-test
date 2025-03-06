<?php

namespace App\GraphQL\Resolvers;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

/**
 * CategoryResolver
 * 
 * Handles GraphQL operations related to categories, integrating with repositories
 * and transforming data for GraphQL responses.
 */
class CategoryResolver
{
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->categoryRepository = new CategoryRepository();
        $this->productRepository = new ProductRepository();
    }

    /**
     * Get all categories with their product counts
     *
     * @return array Array of category data formatted for GraphQL
     */
    public function getCategories(): array
    {
        try {
            $categoriesData = $this->categoryRepository->getAll(withProductCount: true);

            return array_map(function ($categoryData) {
                return Category::fromArray($categoryData)->toArray();
            }, $categoriesData);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching categories: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get a single category by name
     *
     * @param string $name Category name
     * @return array|null Category data formatted for GraphQL
     * @throws \RuntimeException if category fetch fails
     */
    public function getCategory(string $name): ?array
    {
        try {
            $categoryData = $this->categoryRepository->getByName($name);

            if (!$categoryData) {
                return null;
            }

            // Get product count
            $products = $this->categoryRepository->getProducts($name);
            $categoryData['product_count'] = count($products);

            return Category::fromArray($categoryData)->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching category '{$name}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get products for a specific category
     *
     * @param string $categoryName Category name
     * @param array $args Optional arguments for filtering/sorting
     * @return array Array of products in the category
     */
    public function getCategoryProducts(string $categoryName, array $args = []): array
    {
        try {
            // Validate category exists
            $categoryData = $this->categoryRepository->getByName($categoryName);
            if (!$categoryData) {
                return [];
            }

            // Get products data and transform using ProductFactory
            $productsData = $this->productRepository->getAll(['category' => $categoryName]);
            return array_map(function ($productData) {
                return \App\Models\Product\ProductFactory::create($productData)->toArray();
            }, $productsData);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching products for category '{$categoryName}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get category statistics (e.g., total products, subcategories if implemented)
     *
     * @param string $categoryName Category name
     * @return array Statistics data
     */
    public function getCategoryStats(string $categoryName): array
    {
        try {
            $products = $this->categoryRepository->getProducts($categoryName);

            $inStockCount = array_reduce($products, function ($count, $product) {
                return $count + ($product['in_stock'] ? 1 : 0);
            }, 0);

            return [
                'totalProducts' => count($products),
                'inStockProducts' => $inStockCount,
                'outOfStockProducts' => count($products) - $inStockCount
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching category statistics: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get a list of categories sorted by product count
     *
     * @param int $limit Optional limit for number of categories to return
     * @return array Array of categories sorted by product count
     */
    public function getTopCategories(int $limit = 5): array
    {
        try {
            $categoriesData = $this->categoryRepository->getAll(withProductCount: true);

            // Sort by product count descending
            usort($categoriesData, function ($a, $b) {
                return ($b['product_count'] ?? 0) <=> ($a['product_count'] ?? 0);
            });

            // Apply limit and transform to Category models
            $limitedCategories = array_slice($categoriesData, 0, $limit);

            return array_map(function ($categoryData) {
                return Category::fromArray($categoryData)->toArray();
            }, $limitedCategories);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching top categories: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
