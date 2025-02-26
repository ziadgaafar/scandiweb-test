import { ApolloError } from "@apollo/client";

export interface Currency {
  label: string;
  symbol: string;
}

export interface Price {
  amount: number;
  currency: Currency;
}

export interface AttributeItem {
  id: string;
  value: string;
  displayValue: string;
}

export interface ProductAttribute {
  id: string;
  name: string;
  type: string;
  items: AttributeItem[];
}

export interface SelectedAttribute {
  id: string;
  value: string;
}

export interface Product {
  id: string;
  name: string;
  brand: string;
  gallery: string[];
  inStock: boolean;
  attributes: ProductAttribute[];
  prices: Price[];
  description?: string;
}

export interface CartItem extends Product {
  selectedAttributes: SelectedAttribute[];
  quantity: number;
}

export interface CategoryData {
  category: {
    name: string;
    products: Product[];
  };
}

export interface QueryState<T = unknown> {
  loading: boolean;
  error?: ApolloError;
  data?: T;
}

export interface RouterState {
  params: {
    [key: string]: string | undefined;
  };
}

export interface AppState {
  cartItems: CartItem[];
  isCartOpen: boolean;
  selectedCurrency: string;
  currentCategory: string;
}

// Common props interfaces
export interface WithRouterProps extends RouterState, QueryState<unknown> {}

export interface WithCurrencyProps {
  selectedCurrency: string;
}

export interface WithLoadingProps {
  loading: boolean;
}

export interface WithErrorProps {
  error?: Error;
  onRetry?: () => void;
}

// Higher-order component props
export interface WithDataProps<T> {
  data?: T;
  loading: boolean;
  error?: Error;
}

export interface WithCartProps {
  cartItems: CartItem[];
  onAddToCart: (product: Product, attributes: SelectedAttribute[]) => void;
  onRemoveFromCart: (
    productId: string,
    attributes: SelectedAttribute[]
  ) => void;
}
