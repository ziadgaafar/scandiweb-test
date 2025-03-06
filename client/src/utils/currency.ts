import { Price } from "@/types";

export const formatPrice = (
  prices: Price[],
  selectedCurrency: string
): string => {
  const price = prices.find((p) => p.currency.symbol === selectedCurrency);
  return price ? `${selectedCurrency}${price.amount.toFixed(2)}` : "";
};

export const findPrice = (
  prices: Price[],
  selectedCurrency: string
): Price | undefined => {
  return prices.find((p) => p.currency.symbol === selectedCurrency);
};

export const calculateTotal = (
  items: Array<{ prices: Price[]; quantity: number }>,
  selectedCurrency: string
): number => {
  return items.reduce((total, item) => {
    const price = findPrice(item.prices, selectedCurrency);
    return total + (price?.amount || 0) * item.quantity;
  }, 0);
};

export const formatPriceWithSymbol = (
  amount: number,
  symbol: string
): string => {
  return `${symbol}${amount.toFixed(2)}`;
};
