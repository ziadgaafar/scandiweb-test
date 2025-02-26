import { Component } from "react";
import { Link } from "react-router-dom";
import { findPrice, formatPriceWithSymbol } from "../../utils/currency";

interface ProductCardProps {
  id: string;
  name: string;
  brand: string;
  gallery: string[];
  prices: Array<{
    amount: number;
    currency: {
      label: string;
      symbol: string;
    };
  }>;
  inStock: boolean;
  selectedCurrency: string;
  onQuickShop: () => void;
}

class ProductCard extends Component<ProductCardProps> {
  render() {
    const {
      id,
      name,
      brand,
      gallery,
      inStock,
      onQuickShop,
      prices,
      selectedCurrency,
    } = this.props;
    const cardClassName = `product-card ${
      !inStock ? "product-card--out-of-stock" : ""
    }`;
    const productUrl = `/product/${id}`;

    return (
      <div
        className={cardClassName}
        data-testid={`product-${name.toLowerCase().replace(/\s+/g, "-")}`}
      >
        <Link to={productUrl}>
          <div className="product-card__image">
            <img src={gallery[0]} alt={name} />
          </div>
          <h3 className="product-card__title">{`${brand} ${name}`}</h3>
          <p className="product-card__price">
            {formatPriceWithSymbol(
              findPrice(prices, selectedCurrency)?.amount || 0,
              selectedCurrency
            )}
          </p>
        </Link>
        {inStock && (
          <button className="product-card__quick-shop" onClick={onQuickShop}>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
              <path d="M8 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-9.8-3h8.3c.8 0 1.5-.4 1.8-1.1L22 4.2c.3-.7-.2-1.5-1-1.5H5.2L4.7.3C4.6.1 4.4 0 4.2 0H1c-.6 0-1 .4-1 1s.4 1 1 1h2.2l3.4 13.6c.1.2.3.4.5.4z" />
            </svg>
          </button>
        )}
      </div>
    );
  }
}

export default ProductCard;
