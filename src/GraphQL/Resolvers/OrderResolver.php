<?php

namespace App\GraphQL\Resolvers;

use App\GraphQL\Exception\GraphQLException;
use App\GraphQL\Exception\InvalidQuantityException;
use App\Models\Order;

class OrderResolver extends AbstractResolver
{
    private ProductResolver $productResolver;
    private AttributeResolver $attributeResolver;
    private PriceResolver $priceResolver;
    private Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->productResolver = new ProductResolver();
        $this->attributeResolver = new AttributeResolver();
        $this->priceResolver = new PriceResolver();
        $this->orderModel = new Order();
    }

    public function createOrder(array $items): ?array
    {
        try {
            // Validate quantity for all items first
            foreach ($items as $item) {
                $this->validateQuantity($item['productId'], $item['quantity']);
            }

            // Check product availability
            $this->productResolver->checkProductAvailability($items);

            // Validate attributes and prepare order data
            $orderData = $this->prepareOrderData($items);

            // Create order using the Order model
            $orderId = $this->orderModel->createOrder($orderData);

            if (!$orderId) {
                throw new GraphQLException("Failed to create order");
            }

            // Return the created order
            return $this->getOrder($orderId);
        } catch (GraphQLException $e) {
            throw $e;
        } catch (\Exception $e) {
            error_log("Order creation error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    private function validateQuantity(string $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException($productId, $quantity);
        }
    }

    private function prepareOrderData(array $items): array
    {
        $preparedItems = [];
        $totalAmount = 0;

        foreach ($items as $item) {
            // Validate product attributes
            $this->attributeResolver->validateProductAttributes(
                $item['productId'],
                $item['selectedAttributes'] ?? null
            );

            // Calculate price
            $price = $this->priceResolver->getPricesByProduct($item['productId'])[0]['amount'];
            $itemTotal = $price * $item['quantity'];
            $totalAmount += $itemTotal;

            // Process selected attributes if present
            $selectedAttributes = null;
            if (isset($item['selectedAttributes'])) {
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

            $preparedItems[] = [
                'productId' => $item['productId'],
                'quantity' => $item['quantity'],
                'unit_price' => $price,
                'selectedAttributes' => $selectedAttributes
            ];
        }

        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException("Total order amount must be greater than 0");
        }

        return [
            'total_amount' => $totalAmount,
            'items' => $preparedItems
        ];
    }

    public function getOrder(int $orderId): ?array
    {
        return $this->orderModel->getOrderWithDetails($orderId);
    }
}
