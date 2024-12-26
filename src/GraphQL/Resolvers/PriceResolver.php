<?php

namespace App\GraphQL\Resolvers;

class PriceResolver extends AbstractResolver
{
    public function getPricesByProduct(string $productId): array
    {
        $query = "
            SELECT 
                p.amount,
                c.label,
                c.symbol
            FROM prices p
            JOIN currencies c ON p.currency_id = c.id
            WHERE p.product_id = :productId
            ORDER BY c.label ASC
        ";

        $prices = $this->executeQuery($query, ['productId' => $productId]);

        // Format the response to match the expected structure
        return array_map(function ($price) {
            return [
                'amount' => (float) $price['amount'],
                'currency' => [
                    'label' => $price['label'],
                    'symbol' => $price['symbol']
                ]
            ];
        }, $prices);
    }
}
