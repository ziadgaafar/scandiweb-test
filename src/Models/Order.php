<?php

namespace App\Models;

use App\Models\Abstract\AbstractModel;
use PDO;
use PDOException;

class Order extends AbstractModel
{
    protected string $table = 'orders';
    protected array $fillable = ['order_number', 'status', 'total_amount'];

    /**
     * Create a new order with items and their attributes
     *
     * @param array $orderData Order data including items
     * @return int|null Created order ID
     */
    public function createOrder(array $orderData): ?int
    {
        try {
            $this->beginTransaction();

            // Generate unique order number
            $orderData['order_number'] = $this->generateOrderNumber();

            // Create main order record
            $orderId = $this->create([
                'order_number' => $orderData['order_number'],
                'status' => $orderData['status'] ?? 'pending',
                'total_amount' => $orderData['total_amount']
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

                // Create item attributes if present
                if (isset($item['attributes']) && !$this->createOrderItemAttributes($itemId, $item['attributes'])) {
                    $this->rollback();
                    return null;
                }
            }

            $this->commit();
            return $orderId;
        } catch (PDOException $e) {
            error_log("Error creating order: " . $e->getMessage());
            $this->rollback();
            throw new \RuntimeException("Error creating order: " . $e->getMessage());
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
            "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
             VALUES (:order_id, :product_id, :quantity, :unit_price)"
        );

        $success = $stmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price']
        ]);

        return $success ? (int)$this->connection->lastInsertId() : null;
    }

    /**
     * Create order item attributes
     *
     * @param int $itemId Order item ID
     * @param array $attributes Item attributes
     * @return bool Success status
     */
    private function createOrderItemAttributes(int $itemId, array $attributes): bool
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO order_item_attributes (order_item_id, attribute_id, attribute_value) 
             VALUES (:item_id, :attr_id, :attr_value)"
        );

        foreach ($attributes as $attribute) {
            $success = $stmt->execute([
                'item_id' => $itemId,
                'attr_id' => $attribute['id'],
                'attr_value' => $attribute['value']
            ]);

            if (!$success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a unique order number
     *
     * @return string Unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = date('Ymd');
        $random = str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return "ORD-{$prefix}-{$random}";
    }

    /**
     * Get order with all related items and attributes
     *
     * @param int $orderId Order ID
     * @return array|null Order data with items and attributes
     */
    public function getOrderWithItems(int $orderId): ?array
    {
        try {
            // Get order details
            $order = $this->find($orderId);
            if (!$order) {
                return null;
            }

            // Get order items
            $items = $this->getOrderItems($orderId);
            $orderData = $order->toArray();
            $orderData['items'] = $items;

            return $orderData;
        } catch (PDOException $e) {
            throw new \RuntimeException("Error fetching order: " . $e->getMessage());
        }
    }

    /**
     * Get items for an order
     *
     * @param int $orderId Order ID
     * @return array Order items with attributes
     */
    private function getOrderItems(int $orderId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM order_items WHERE order_id = :order_id"
        );
        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item['attributes'] = $this->getItemAttributes($item['id']);
        }

        return $items;
    }

    /**
     * Get attributes for an order item
     *
     * @param int $itemId Order item ID
     * @return array Item attributes
     */
    private function getItemAttributes(int $itemId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM order_item_attributes WHERE order_item_id = :item_id"
        );
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
