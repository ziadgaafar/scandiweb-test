<?php

namespace App\GraphQL\Resolvers;

class OrderResolver extends AbstractResolver
{
    private ProductResolver $productResolver;
    private AttributeResolver $attributeResolver;

    public function __construct()
    {
        parent::__construct();
        $this->productResolver = new ProductResolver();
        $this->attributeResolver = new AttributeResolver();
    }

    public function createOrder(array $items): ?array
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Validate products are in stock
            if (!$this->productResolver->checkProductAvailability($items)) {
                throw new \RuntimeException("Some products are not available");
            }

            // Validate all selected attributes and calculate total amount
            $totalAmount = 0;
            foreach ($items as $item) {
                // Validate product
                $product = $this->productResolver->getProduct($item['productId']);
                if (!$product) {
                    throw new \RuntimeException("Product not found: " . $item['productId']);
                }

                // Validate attributes if present
                if (
                    isset($item['selectedAttributes']) &&
                    !$this->attributeResolver->validateAttributeValues(
                        $item['productId'],
                        $item['selectedAttributes']
                    )
                ) {
                    throw new \RuntimeException("Invalid attribute selection for product: " . $item['productId']);
                }

                // Calculate total amount
                $price = $product['prices'][0]['amount']; // Using first price for simplicity
                $itemTotal = $price * $item['quantity'];
                $totalAmount += $itemTotal;
            }

            // Create order record with total amount
            $orderId = $this->createOrderRecord($totalAmount);

            // Create order items
            foreach ($items as $item) {
                $this->createOrderItem($orderId, $item);
            }

            // Commit transaction
            $this->db->commit();

            // Return created order
            return $this->getOrder($orderId);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollBack();

            // Log error for debugging
            error_log("Order creation error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            throw new \RuntimeException("Order creation failed: " . $e->getMessage());
        }
    }

    private function createOrderRecord(float $totalAmount): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO orders (status, total_amount, currency_id, created_at) VALUES ('pending', :total, 1, NOW())"
        );

        $stmt->execute([
            'total' => $totalAmount
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function createOrderItem(int $orderId, array $item): void
    {
        // Get product details
        $product = $this->productResolver->getProduct($item['productId']);
        if (!$product) {
            throw new \RuntimeException("Product not found");
        }

        // Calculate price
        $price = $product['prices'][0]['amount'];
        $total = $price * $item['quantity'];

        // Insert order item
        $stmt = $this->db->prepare(
            "INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price, total_price) 
            VALUES (:orderId, :productId, :quantity, :unitPrice, :totalPrice)"
        );

        $stmt->execute([
            'orderId' => $orderId,
            'productId' => $item['productId'],
            'quantity' => $item['quantity'],
            'unitPrice' => $price,
            'totalPrice' => $total
        ]);

        $orderItemId = (int)$this->db->lastInsertId();

        // Insert selected attributes if any
        if (isset($item['selectedAttributes'])) {
            $this->saveOrderItemAttributes($orderItemId, $item['selectedAttributes']);
        }
    }

    private function saveOrderItemAttributes(int $orderItemId, array $attributes): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO order_item_attributes 
            (order_item_id, attribute_id, selected_value) 
            VALUES (:orderItemId, :attributeId, :value)"
        );

        foreach ($attributes as $attr) {
            $stmt->execute([
                'orderItemId' => $orderItemId,
                'attributeId' => $attr['id'],
                'value' => $attr['value']
            ]);
        }
    }

    public function getOrder(int $orderId): ?array
    {
        // Fetch order with items and additional details
        $order = $this->executeSingle(
            "SELECT 
                o.*,
                c.label as currency_label,
                c.symbol as currency_symbol
            FROM orders o
            JOIN currencies c ON o.currency_id = c.id
            WHERE o.id = :orderId",
            ['orderId' => $orderId]
        );

        if ($order) {
            // Fetch order items with their details
            $order['items'] = $this->getOrderItemsWithDetails($orderId);
        }

        return $order;
    }

    public function getOrderItemsWithDetails(int $orderId): array
    {
        return $this->executeQuery(
            "SELECT 
                oi.*,
                p.name as product_name,
                p.id as product_id
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :orderId",
            ['orderId' => $orderId]
        );
    }

    public function getOrderItems(int $orderId): array
    {
        return $this->executeQuery(
            "SELECT * FROM order_items WHERE order_id = :orderId",
            ['orderId' => $orderId]
        );
    }
}
