import { Component } from "react";
import { ApolloError } from "@apollo/client";
import { CartItem, Product, SelectedAttribute } from "../../types";
import "./CartOverlay.scss";
import {
  calculateTotal,
  findPrice,
  formatPriceWithSymbol,
} from "@/utils/currency";
import { parseGraphQLError, retryOperation } from "../../utils/errorHandling";

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
}

class CartOverlay extends Component<CartOverlayProps> {
  handleQuantityChange = (item: CartItem, increase: boolean) => {
    const { onAddItem, onRemoveItem } = this.props;
    if (increase) {
      onAddItem(item, item.selectedAttributes);
    } else {
      onRemoveItem(item.id, item.selectedAttributes);
    }
  };

  render() {
    const { items } = this.props;
    const total = calculateTotal(items, this.props.selectedCurrency);
    const itemCount = items.reduce((acc, item) => acc + item.quantity, 0);

    return (
      <div className="cart-overlay">
        <h2 className="cart-overlay__title">
          My Bag,{" "}
          <span>
            {itemCount} {itemCount === 1 ? "Item" : "Items"}
          </span>
        </h2>
        <div className="cart-overlay__items">
          {items.map((item, index) => (
            <div key={`${item.id}-${index}`} className="cart-overlay__item">
              <div className="cart-overlay__item-info">
                <h3>{item.brand}</h3>
                <h4>{item.name}</h4>
                <p className="cart-overlay__item-price">
                  {formatPriceWithSymbol(
                    findPrice(item.prices, this.props.selectedCurrency)
                      ?.amount || 0,
                    this.props.selectedCurrency
                  )}
                </p>
                {item.attributes?.length > 0 &&
                  item.attributes.map((attr) => (
                    <div
                      key={attr.id}
                      className="cart-overlay__item-attribute"
                      data-testid={`cart-item-attribute-${attr.name.toLowerCase()}`}
                    >
                      <p>{attr.name}:</p>
                      <div className="cart-overlay__item-options">
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
                                isSelected
                                  ? `${baseTestId}-selected`
                                  : baseTestId
                              }
                              className={`cart-overlay__item-option ${
                                attr.type === "swatch" ? "swatch" : ""
                              } ${isSelected ? "selected" : ""}`}
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
              <div className="cart-overlay__item-quantity">
                <button
                  data-testid="cart-item-amount-increase"
                  onClick={() => this.handleQuantityChange(item, true)}
                >
                  +
                </button>
                <span data-testid="cart-item-amount">{item.quantity}</span>
                <button
                  data-testid="cart-item-amount-decrease"
                  onClick={() => this.handleQuantityChange(item, false)}
                >
                  -
                </button>
              </div>
              <div className="cart-overlay__item-gallery">
                <img src={item.gallery[0]} alt={item.name} />
              </div>
            </div>
          ))}
        </div>
        <div className="cart-overlay__total">
          <span>Total</span>
          <span data-testid="cart-total">
            {formatPriceWithSymbol(total, this.props.selectedCurrency)}
          </span>
        </div>
        <div className="cart-overlay__actions">
          <button
            className="cart-overlay__checkout"
            disabled={items.length === 0}
            onClick={async () => {
              try {
                // Placeholder for actual mutation call
                await retryOperation(
                  async () => {
                    const result = await Promise.resolve(); // Replace with actual mutation
                    return result;
                  },
                  { maxAttempts: 3, delayMs: 1000 }
                );
              } catch (error) {
                const errorMessage = parseGraphQLError(error as ApolloError);
                alert(
                  errorMessage || "Failed to place order. Please try again."
                );
              }
            }}
          >
            PLACE ORDER
          </button>
        </div>
      </div>
    );
  }
}

export default CartOverlay;
