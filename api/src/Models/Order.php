<?php

namespace App\Models;

use App\Models\Abstract\AbstractModel;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use Exception;

class Order extends AbstractModel
{
    private const STATUSES = [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ];

    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private array $validationErrors = [];

    public function __construct()
    {
        parent::__construct();
        $this->orderRepository = new OrderRepository();
        $this->productRepository = new ProductRepository();
    }

    public function createOrder(array $orderData): ?array
    {
        try {
            // Validate order data
            if (!$this->validateOrderData($orderData)) {
                throw new \InvalidArgumentException(
                    "Invalid order data: " . implode(", ", $this->validationErrors)
                );
            }

            // Check product availability and prepare items
            $preparedItems = $this->prepareOrderItems($orderData['items']);

            // Calculate total from prepared items
            $totalAmount = array_reduce($preparedItems, function ($carry, $item) {
                return $carry + ($item['unit_price'] * $item['quantity']);
            }, 0.0);

            // Round to 2 decimal places
            $totalAmount = round($totalAmount, 2);

            // Prepare final order data
            $finalOrderData = [
                'total_amount' => $totalAmount,
                'currency_id' => 1, // Default to USD for now
                'status' => 'pending',
                'items' => $preparedItems
            ];

            // Create order using repository
            $orderId = $this->orderRepository->createOrder($finalOrderData);
            if (!$orderId) {
                return null;
            }

            // Return complete order data
            return $this->orderRepository->getOrderById($orderId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function validateOrderData(array $orderData): bool
    {
        $this->validationErrors = [];

        if (empty($orderData['items'])) {
            $this->validationErrors[] = "Order must contain items";
            return false;
        }

        foreach ($orderData['items'] as $item) {
            if (!isset($item['productId'])) {
                $this->validationErrors[] = "Each item must have a productId";
                return false;
            }

            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                $this->validationErrors[] = "Each item must have a valid quantity";
                return false;
            }
        }

        return empty($this->validationErrors);
    }

    private function prepareOrderItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            // Validate quantity
            if ($item['quantity'] <= 0) {
                throw new Exception("Invalid quantity for product: {$item['productId']}");
            }

            // Get product details and validate availability
            $product = $this->productRepository->getById($item['productId']);

            if (!$product['inStock']) {
                throw new Exception("Product not available: {$item['productId']}");
            }

            // Get product price (assuming USD for now)
            $price = 0;
            if (!empty($product['prices'])) {
                foreach ($product['prices'] as $priceData) {
                    if ($priceData['currency']['label'] === 'USD') {
                        $price = (float)$priceData['amount'];
                        break;
                    }
                }
            }

            if ($price <= 0) {
                throw new \RuntimeException("Invalid product price for: {$item['productId']}");
            }

            // Prepare item data
            $preparedItems[] = [
                'product_id' => $item['productId'],
                'quantity' => (int)$item['quantity'],
                'unit_price' => $price,
                'selected_attributes' => isset($item['selectedAttributes'])
                    ? json_encode($item['selectedAttributes'])
                    : null
            ];
        }

        return $preparedItems;
    }

    public function updateStatus(int $orderId, string $newStatus): bool
    {
        // Validate status
        if (!in_array($newStatus, self::STATUSES)) {
            throw new \InvalidArgumentException("Invalid order status: {$newStatus}");
        }

        // Get current order
        $order = $this->orderRepository->getOrderById($orderId);
        if (!$order) {
            throw new \RuntimeException("Order not found: {$orderId}");
        }

        // Validate status transition
        if (!$this->isValidStatusTransition($order['status'], $newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid status transition from {$order['status']} to {$newStatus}"
            );
        }

        // Update status using repository
        return $this->orderRepository->updateOrderStatus($orderId, $newStatus);
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [], // Final state
            'cancelled' => []  // Final state
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    public function getOrder(int $orderId): ?array
    {
        return $this->orderRepository->getOrderById($orderId);
    }

    public function formatForGraphQL(array $orderData): array
    {
        return [
            'id' => $orderData['id'],
            'total' => (float)$orderData['total_amount'],
            'status' => $orderData['status'],
            'createdAt' => $orderData['created_at'],
            'items' => array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'selected_attributes' => $item['selected_attributes']
                        ? $item['selected_attributes']
                        : null
                ];
            }, $orderData['items']),
            '__typename' => 'Order'
        ];
    }
}
