<?php

namespace App\GraphQL\Resolvers;

use App\Models\Order;
use App\GraphQL\Exception\GraphQLException;
use App\GraphQL\Exception\InvalidQuantityException;
use App\GraphQL\Exception\ProductNotFoundException;
use App\GraphQL\Exception\ProductUnavailableException;
use App\GraphQL\Exception\MissingAttributesException;
use App\GraphQL\Exception\InvalidAttributeException;

/**
 * Resolver for Order-related GraphQL operations
 */
class OrderResolver
{
    private Order $orderModel;
    private ProductResolver $productResolver;
    private AttributeResolver $attributeResolver;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->productResolver = new ProductResolver();
        $this->attributeResolver = new AttributeResolver();
    }

    /**
     * Create a new order
     *
     * @param array $items Order items from GraphQL input
     * @return array|null Created order data or null on failure
     * @throws GraphQLException
     */
    public function createOrder(array $items): ?array
    {
        try {
            // Validate items structure
            $this->validateItemsStructure($items);

            // Validate and prepare items
            $preparedItems = $this->validateAndPrepareItems($items);

            // Create order using model
            $orderData = $this->orderModel->createOrder([
                'items' => $preparedItems
            ]);

            if (!$orderData) {
                throw new GraphQLException(
                    "Failed to create order",
                    "user",
                    "ORDER_CREATION_FAILED"
                );
            }

            // Format response for GraphQL
            return $this->orderModel->formatForGraphQL($orderData);
        } catch (
            InvalidQuantityException |
            ProductNotFoundException |
            ProductUnavailableException |
            MissingAttributesException |
            InvalidAttributeException $e
        ) {
            // Re-throw known exceptions
            throw $e;
        } catch (\Exception $e) {
            throw new GraphQLException(
                "An error occurred while creating the order",
                "internal",
                "ORDER_ERROR"
            );
        }
    }

    /**
     * Validate items structure from GraphQL input
     *
     * @param array $items Items to validate
     * @throws GraphQLException If structure is invalid
     */
    private function validateItemsStructure(array $items): void
    {
        if (empty($items)) {
            throw new GraphQLException(
                "Order must contain at least one item",
                "user",
                "INVALID_ORDER"
            );
        }

        foreach ($items as $item) {
            if (!isset($item['productId'])) {
                throw new GraphQLException(
                    "Each item must have a productId",
                    "user",
                    "INVALID_ITEM"
                );
            }

            if (!isset($item['quantity'])) {
                throw new GraphQLException(
                    "Each item must have a quantity",
                    "user",
                    "INVALID_ITEM"
                );
            }
        }
    }

    /**
     * Validate and prepare items for order creation
     *
     * @param array $items Raw items from GraphQL input
     * @return array Prepared items
     * @throws Various exceptions based on validation results
     */
    private function validateAndPrepareItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            // Validate product exists and is available
            $product = $this->productResolver->getProduct($item['productId']);

            if (!$product['inStock']) {
                throw new ProductUnavailableException($item['productId']);
            }

            // Validate quantity
            if ($item['quantity'] <= 0) {
                throw new InvalidQuantityException($item['productId'], $item['quantity']);
            }

            // Validate attributes if present
            if (isset($item['selectedAttributes'])) {
                $this->attributeResolver->validateProductAttributes(
                    $item['productId'],
                    $item['selectedAttributes']
                );
            }

            // Add to prepared items
            $preparedItems[] = [
                'productId' => $item['productId'],
                'quantity' => $item['quantity'],
                'selectedAttributes' => $item['selectedAttributes'] ?? null
            ];
        }

        return $preparedItems;
    }

    /**
     * Get order by ID
     *
     * @param int $orderId Order ID
     * @return array|null Order data
     * @throws GraphQLException If order not found
     */
    public function getOrder(int $orderId): ?array
    {
        try {
            $orderData = $this->orderModel->getOrder($orderId);

            if (!$orderData) {
                throw new GraphQLException(
                    "Order not found: {$orderId}",
                    "user",
                    "ORDER_NOT_FOUND"
                );
            }

            return $this->orderModel->formatForGraphQL($orderData);
        } catch (GraphQLException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GraphQLException(
                "An error occurred while fetching the order",
                "internal",
                "ORDER_ERROR"
            );
        }
    }

    /**
     * Update order status
     *
     * @param int $orderId Order ID
     * @param string $status New status
     * @return array Updated order data
     * @throws GraphQLException If update fails
     */
    public function updateOrderStatus(int $orderId, string $status): array
    {
        try {
            $success = $this->orderModel->updateStatus($orderId, $status);

            if (!$success) {
                throw new GraphQLException(
                    "Failed to update order status",
                    "user",
                    "STATUS_UPDATE_FAILED"
                );
            }

            $orderData = $this->orderModel->getOrder($orderId);
            return $this->orderModel->formatForGraphQL($orderData);
        } catch (\InvalidArgumentException $e) {
            throw new GraphQLException(
                $e->getMessage(),
                "user",
                "INVALID_STATUS"
            );
        } catch (GraphQLException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new GraphQLException(
                "An error occurred while updating the order",
                "internal",
                "ORDER_ERROR"
            );
        }
    }

    /**
     * Format error for GraphQL response
     *
     * @param \Exception $e Exception to format
     * @return array Formatted error
     */
    private function formatError(\Exception $e): array
    {
        return [
            'message' => $e->getMessage(),
            'extensions' => [
                'category' => $e instanceof GraphQLException ? $e->getCategory() : 'internal',
                'code' => $e instanceof GraphQLException ? $e->getErrorCode() : 'INTERNAL_ERROR'
            ]
        ];
    }
}
