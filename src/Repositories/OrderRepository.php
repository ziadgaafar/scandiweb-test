<?php

namespace App\Repositories;

use PDO;
use App\Services\Database\MySQLConnection;

class OrderRepository
{
    private PDO $connection;
    private string $table = 'orders';

    public function __construct()
    {
        $this->connection = MySQLConnection::getInstance()->getConnection();
    }

    public function createOrder(array $orderData): ?int
    {
        try {
            $this->connection->beginTransaction();

            // Validate required data
            if (!isset($orderData['total_amount'])) {
                throw new \RuntimeException("Order total_amount is required");
            }

            if (!isset($orderData['items']) || empty($orderData['items'])) {
                throw new \RuntimeException("Order items are required");
            }

            // Create main order record
            $query = "
                INSERT INTO {$this->table} 
                (total_amount, currency_id, status) 
                VALUES (:total_amount, :currency_id, :status)
            ";

            $stmt = $this->connection->prepare($query);
            $success = $stmt->execute([
                'total_amount' => $orderData['total_amount'],
                'currency_id' => $orderData['currency_id'] ?? 1,
                'status' => $orderData['status'] ?? 'pending'
            ]);

            if (!$success) {
                throw new \RuntimeException("Failed to insert order");
            }

            $orderId = (int)$this->connection->lastInsertId();

            // Create order items
            foreach ($orderData['items'] as $item) {
                $itemQuery = "
                    INSERT INTO order_items 
                    (order_id, product_id, quantity, unit_price, selected_attributes) 
                    VALUES (:order_id, :product_id, :quantity, :unit_price, :selected_attributes)
                ";

                $itemStmt = $this->connection->prepare($itemQuery);
                $itemSuccess = $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'selected_attributes' => $item['selected_attributes'] ?? null
                ]);

                if (!$itemSuccess) {
                    throw new \RuntimeException("Failed to insert order item");
                }
            }

            $this->connection->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            error_log("Order creation error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getOrderById(int $orderId): ?array
    {
        try {
            // Get main order data
            $query = "
                SELECT 
                    o.*,
                    c.label as currency_label,
                    c.symbol as currency_symbol,
                    DATE_FORMAT(o.created_at, '%Y-%m-%dT%H:%i:%sZ') as created_at
                FROM {$this->table} o
                JOIN currencies c ON o.currency_id = c.id
                WHERE o.id = :orderId
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(['orderId' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return null;
            }

            // Get order items
            $itemsQuery = "
                SELECT 
                    oi.*,
                    p.name as product_name,
                    p.brand as product_brand,
                    p.description as product_description
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :orderId
            ";

            $itemsStmt = $this->connection->prepare($itemsQuery);
            $itemsStmt->execute(['orderId' => $orderId]);
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (\PDOException $e) {
            error_log("Error fetching order: " . $e->getMessage());
            throw new \RuntimeException("Failed to fetch order");
        }
    }

    public function updateOrderStatus(int $orderId, string $status): bool
    {
        try {
            $query = "
                UPDATE {$this->table} 
                SET status = :status 
                WHERE id = :orderId
            ";

            $stmt = $this->connection->prepare($query);
            return $stmt->execute([
                'orderId' => $orderId,
                'status' => $status
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            throw new \RuntimeException("Failed to update order status");
        }
    }

    public function calculateOrderTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $price = (float)($item['unit_price'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            $total += $price * $quantity;
        }
        return round($total, 2);
    }
}
