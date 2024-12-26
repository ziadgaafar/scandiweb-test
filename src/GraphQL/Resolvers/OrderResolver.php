<?php

namespace App\GraphQL\Resolvers;

class OrderResolver extends AbstractResolver
{
    private ProductResolver $productResolver;
    private AttributeResolver $attributeResolver;
    private PriceResolver $priceResolver;

    public function __construct()
    {
        parent::__construct();
        $this->productResolver = new ProductResolver();
        $this->attributeResolver = new AttributeResolver();
        $this->priceResolver = new PriceResolver();
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
                $price = $this->priceResolver->getPricesByProduct($item['productId'])[0]['amount'];
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
            "INSERT INTO orders (status, total_amount, currency_id, created_at) 
         VALUES ('pending', :total_amount, 1, NOW())"
        );

        $stmt->execute([
            'total_amount' => $totalAmount
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function createOrderItem(int $orderId, array $item): int
    {
        // Get product details
        $product = $this->productResolver->getProduct($item['productId']);
        if (!$product) {
            throw new \RuntimeException("Product not found");
        }

        // Calculate price
        $price = $this->priceResolver->getPricesByProduct($item['productId'])[0]['amount'];

        // Process selected attributes if present
        $selectedAttributes = null;
        if (isset($item['selectedAttributes'])) {
            // Validate and enrich attribute data
            $enrichedAttributes = [];
            foreach ($item['selectedAttributes'] as $attr) {
                $attributeSet = $this->attributeResolver->getAttributeSet($attr['id']);
                if (!$attributeSet) {
                    throw new \RuntimeException("Invalid attribute: " . $attr['id']);
                }

                $enrichedAttributes[] = [
                    'id' => $attr['id'],
                    'name' => $attributeSet['name'],
                    'value' => $attr['value']
                ];
            }
            $selectedAttributes = json_encode($enrichedAttributes);
        }

        // Insert order item
        $stmt = $this->db->prepare(
            "INSERT INTO order_items 
        (order_id, product_id, quantity, unit_price, selected_attributes) 
        VALUES (:orderId, :productId, :quantity, :unitPrice, :selectedAttributes)"
        );

        $stmt->execute([
            'orderId' => $orderId,
            'productId' => $item['productId'],
            'quantity' => $item['quantity'],
            'unitPrice' => $price,
            'selectedAttributes' => $selectedAttributes
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getOrder(int $orderId): ?array
    {
        // Fetch order with items and additional details
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
            // Fetch order items with their details
            $order['items'] = $this->getOrderItemsWithDetails($orderId);
            $order['total'] = (float) $order['total_amount'];
            $order['createdAt'] = $order['createdAt'] ?? date('Y-m-d\TH:i:s\Z');
        }

        return $order;
    }

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

    public function getOrderItems(int $orderId): array
    {
        return $this->executeQuery(
            "SELECT * FROM order_items WHERE order_id = :orderId",
            ['orderId' => $orderId]
        );
    }
}
