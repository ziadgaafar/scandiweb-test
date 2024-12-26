<?php

namespace App\Models;

use App\Models\Abstract\AbstractModel;
use PDO;
use PDOException;

class Order extends AbstractModel
{
    protected string $table = 'orders';
    protected array $fillable = ['status', 'total_amount', 'currency_id'];

    /**
     * Create a new order with items and their attributes
     *
     * @param array $orderData Order data including items
     * @return int|null Created order ID
     * @throws \RuntimeException if order creation fails
     */
    public function createOrder(array $orderData): ?int
    {
        try {
            $this->beginTransaction();

            // Create main order record
            $orderId = $this->create([
                'status' => 'pending',
                'total_amount' => $orderData['total_amount'],
                'currency_id' => 1 // Default currency ID
            ]);

            if (!$orderId) {
                $this->rollback();
                return null;
            }

            // Create order items
            foreach ($orderData['items'] as $item) {
                $itemId = $this->createOrderItem($orderId, $item);

                if (!$itemId) {
                    $this->rollback();
                    return null;
                }
            }

            $this->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->rollback();
            throw new \RuntimeException("Error creating order: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create an order item
     *
     * @param int $orderId Order ID
     * @param array $item Item data
     * @return int|null Created item ID
     */
    private function createOrderItem(int $orderId, array $item): ?int
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price, selected_attributes) 
            VALUES (:orderId, :productId, :quantity, :unitPrice, :selectedAttributes)"
        );

        $success = $stmt->execute([
            'orderId' => $orderId,
            'productId' => $item['productId'],
            'quantity' => $item['quantity'],
            'unitPrice' => $item['unit_price'],
            'selectedAttributes' => $item['selectedAttributes'] ?? null
        ]);

        return $success ? (int)$this->connection->lastInsertId() : null;
    }

    /**
     * Get order with all related details
     *
     * @param int $orderId Order ID
     * @return array|null Order data with items and currency details
     */
    public function getOrderWithDetails(int $orderId): ?array
    {
        try {
            $query = "
                SELECT 
                    o.*,
                    c.label as currency_label,
                    c.symbol as currency_symbol,
                    DATE_FORMAT(o.created_at, '%Y-%m-%dT%H:%i:%sZ') as createdAt
                FROM orders o
                JOIN currencies c ON o.currency_id = c.id
                WHERE o.id = :orderId
            ";

            $order = $this->executeSingle($query, ['orderId' => $orderId]);

            if ($order) {
                $order['items'] = $this->getOrderItemsWithDetails($orderId);
                $order['total'] = (float) $order['total_amount'];
                $order['createdAt'] = $order['createdAt'] ?? date('Y-m-d\TH:i:s\Z');
            }

            return $order;
        } catch (PDOException $e) {
            throw new \RuntimeException("Error fetching order: " . $e->getMessage());
        }
    }

    /**
     * Get order items with product details
     *
     * @param int $orderId Order ID
     * @return array Order items with product details
     */
    private function getOrderItemsWithDetails(int $orderId): array
    {
        $query = "
            SELECT 
                oi.*,
                p.name as product_name,
                p.brand as product_brand,
                p.id as product_id
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :orderId
        ";

        return $this->executeQuery($query, ['orderId' => $orderId]);
    }
}
