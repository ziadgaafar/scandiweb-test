<?php

namespace App\Repositories;

use PDO;
use App\Services\Database\MySQLConnection;

class PriceRepository
{
    private PDO $connection;
    private string $table = 'prices';

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    /**
     * Get all prices for a specific product with currency information
     * 
     * @param string $productId Product ID
     * @return array Array of prices with currency information
     */
    public function getPricesByProduct(string $productId): array
    {
        try {
            $query = "
                SELECT 
                    p.amount,
                    c.label as currency_label,
                    c.symbol as currency_symbol
                FROM {$this->table} p
                JOIN currencies c ON p.currency_id = c.id
                WHERE p.product_id = :productId
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(['productId' => $productId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product prices: " . $e->getMessage());
        }
    }

    /**
     * Get specific price by product and currency
     * 
     * @param string $productId Product ID
     * @param string $currencyLabel Currency label (e.g., 'USD')
     * @return array|null Price data or null if not found
     */
    public function getPrice(string $productId, string $currencyLabel): ?array
    {
        try {
            $query = "
                SELECT 
                    p.amount,
                    c.label as currency_label,
                    c.symbol as currency_symbol
                FROM {$this->table} p
                JOIN currencies c ON p.currency_id = c.id
                WHERE p.product_id = :productId
                AND c.label = :currencyLabel
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute([
                'productId' => $productId,
                'currencyLabel' => $currencyLabel
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Error fetching product price: " . $e->getMessage());
        }
    }
}
