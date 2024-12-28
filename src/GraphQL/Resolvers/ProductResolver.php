<?php

namespace App\GraphQL\Resolvers;

use App\Models\Product\SimpleProduct;

class ProductResolver extends AbstractResolver
{
    private SimpleProduct $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new SimpleProduct();
    }

    public function getProducts(?array $args = []): array
    {
        return $this->productModel->getProducts($args);
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
        return $this->productModel->getProduct($id);
    }

    public function getProductGallery(string $productId): array
    {
        return $this->productModel->getProductGallery($productId);
    }

    public function checkProductAvailability(array $items): bool
    {
        return $this->productModel->checkProductsAvailability($items);
    }
}
