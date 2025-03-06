import React from "react";
import { ApolloError, useMutation } from "@apollo/client";
import { CREATE_ORDER } from "@/graphql/queries";
import { CartItem, Product, SelectedAttribute } from "@/types";
import {
  calculateTotal,
  findPrice,
  formatPriceWithSymbol,
} from "@/utils/currency";
import { parseGraphQLError, retryOperation } from "@/utils/errorHandling";

interface CartOverlayProps {
  items: CartItem[];
  selectedCurrency: string;
  onRemoveItem: (
    productId: string,
    selectedAttributes: SelectedAttribute[]
  ) => void;
  onAddItem: (
    product: Product,
    selectedAttributes: SelectedAttribute[]
  ) => void;
  clearCart: () => void;
}

const CartOverlay: React.FC<CartOverlayProps> = ({
  items,
  selectedCurrency,
  onAddItem,
  onRemoveItem,
  clearCart,
}) => {
  const [createOrder, { loading }] = useMutation(CREATE_ORDER);

  const handleQuantityChange = (item: CartItem, increase: boolean) => {
    if (increase) {
      onAddItem(item, item.selectedAttributes);
    } else {
      onRemoveItem(item.id, item.selectedAttributes);
    }
  };

  const total = calculateTotal(items, selectedCurrency);
  const itemCount = items.reduce((acc, item) => acc + item.quantity, 0);

  return (
    <div
      data-testid="cart-overlay"
      className="absolute top-20 right-[72px] w-96 max-h-[calc(100vh-100px)] bg-background p-8 px-4 z-100 overflow-y-auto"
    >
      <h2 className="font-bold text-base leading-[160%] mb-8">
        My Bag,{" "}
        <span className="font-medium">
          {itemCount} {itemCount === 1 ? "Item" : "Items"}
        </span>
      </h2>
      <div className="mb-8 space-y-12">
        {items.map((item, index) => (
          <div key={`${item.id}-${index}`} className="flex gap-2 first:pt-0">
            <div className="flex-1">
              <h3 className="font-light text-base leading-[160%] mb-1">
                {item.brand}
              </h3>
              <h4 className="font-light text-base leading-[160%] mb-2">
                {item.name}
              </h4>
              <p className="font-medium text-base leading-[160%] mb-3">
                {formatPriceWithSymbol(
                  findPrice(item.prices, selectedCurrency)?.amount || 0,
                  selectedCurrency
                )}
              </p>
              {item.attributes?.length > 0 &&
                item.attributes.map((attr) => (
                  <div
                    key={attr.id}
                    className="mb-2"
                    data-testid={`cart-item-attribute-${attr.name.toLowerCase()}`}
                  >
                    <p className="font-bold text-sm leading-4 mb-2">
                      {attr.name}:
                    </p>
                    <div className="flex flex-wrap gap-2 max-w-full">
                      {attr.items.map((option) => {
                        const isSelected = item.selectedAttributes.some(
                          (selected) =>
                            selected.id === attr.id &&
                            selected.value === option.value
                        );
                        const baseTestId = `cart-item-attribute-${attr.name.toLowerCase()}-${option.value.toLowerCase()}`;
                        return (
                          <div
                            key={option.id}
                            data-testid={
                              isSelected ? `${baseTestId}-selected` : baseTestId
                            }
                            className={`flex items-center justify-center text-sm
                                ${
                                  attr.type === "swatch"
                                    ? "min-w-4 h-4"
                                    : "min-w-6 h-6 border border-text px-1"
                                }
                                ${
                                  isSelected
                                    ? attr.type === "swatch"
                                      ? "outline-2 outline-primary outline-offset-1"
                                      : "bg-text text-background"
                                    : ""
                                }`}
                            style={
                              attr.type === "swatch"
                                ? { backgroundColor: option.value }
                                : undefined
                            }
                          >
                            {attr.type !== "swatch" && option.value}
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))}
            </div>
            <div className="flex flex-col justify-between items-center">
              <button
                className="w-6 h-6 flex items-center justify-center border border-text text-base bg-transparent cursor-pointer transition-all duration-300 hover:bg-text hover:text-background"
                data-testid="cart-item-amount-increase"
                onClick={() => handleQuantityChange(item, true)}
              >
                +
              </button>
              <span className="font-medium" data-testid="cart-item-amount">
                {item.quantity}
              </span>
              <button
                className="w-6 h-6 flex items-center justify-center border border-text text-base bg-transparent cursor-pointer transition-all duration-300 hover:bg-text hover:text-background"
                data-testid="cart-item-amount-decrease"
                onClick={() => handleQuantityChange(item, false)}
              >
                -
              </button>
            </div>
            <div className="w-32">
              <img
                src={item.gallery[0]}
                alt={item.name}
                className="h-full object-contain"
              />
            </div>
          </div>
        ))}
      </div>
      <div className="flex justify-between items-center mb-8 font-medium text-base leading-[18px]">
        <span className="font-bold">Total</span>
        <span data-testid="cart-total">
          {formatPriceWithSymbol(total, selectedCurrency)}
        </span>
      </div>
      <div className="flex gap-3">
        <button
          className="flex-1 h-[43px] font-semibold text-sm leading-[120%] flex items-center justify-center uppercase cursor-pointer transition-all duration-300 bg-primary text-background disabled:opacity-50 disabled:cursor-not-allowed"
          disabled={items.length === 0 || loading}
          onClick={handlePlaceOrder}
        >
          {loading ? "PLACING ORDER..." : "PLACE ORDER"}
        </button>
      </div>
    </div>
  );

  async function handlePlaceOrder() {
    try {
      // Transform cart items into mutation format
      const orderItems = items.map((item) => ({
        productId: item.id,
        quantity: item.quantity,
        selectedAttributes: item.selectedAttributes,
      }));

      const result = await retryOperation(
        async () => {
          const response = await createOrder({
            variables: {
              items: orderItems,
            },
          });

          if (!response.data?.createOrder) {
            throw new Error("Failed to create order");
          }

          return response;
        },
        { maxAttempts: 3, delayMs: 1000 }
      );

      // Clear the cart after successful order placement
      if (result.data?.createOrder) {
        clearCart();
      }
    } catch (error) {
      const errorMessage = parseGraphQLError(error as ApolloError);
      alert(errorMessage || "Failed to place order. Please try again.");
    }
  }
};

export default CartOverlay;
