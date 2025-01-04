<?php

namespace App\GraphQL\Resolvers;

use App\Repositories\PriceRepository;

class PriceResolver
{
    private PriceRepository $repository;

    public function __construct()
    {
        $this->repository = new PriceRepository();
    }

    /**
     * Get prices for a specific product
     * 
     * @param string $productId Product ID
     * @return array Array of prices with currency information
     */
    public function getPricesByProduct(string $productId): array
    {
        try {
            $prices = $this->repository->getPricesByProduct($productId);

            // Transform prices into GraphQL format
            return array_map(function ($price) {
                return [
                    'amount' => (float) $price['amount'],
                    'currency' => [
                        'label' => $price['currency_label'],
                        'symbol' => $price['currency_symbol'],
                        '__typename' => 'Currency'
                    ],
                    '__typename' => 'Price'
                ];
            }, $prices);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching prices for product {$productId}: " . $e->getMessage()
            );
        }
    }

    /**
     * Get price in specific currency
     * 
     * @param string $productId Product ID
     * @param string $currencyLabel Currency label (e.g., 'USD')
     * @return array|null Price data or null if not found
     */
    public function getPrice(string $productId, string $currencyLabel = 'USD'): ?array
    {
        try {
            $price = $this->repository->getPrice($productId, $currencyLabel);

            if (!$price) {
                return null;
            }

            return [
                'amount' => (float) $price['amount'],
                'currency' => [
                    'label' => $price['currency_label'],
                    'symbol' => $price['currency_symbol'],
                    '__typename' => 'Currency'
                ],
                '__typename' => 'Price'
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error fetching price for product {$productId}: " . $e->getMessage()
            );
        }
    }
}
