import { Component } from "react";
import { ApolloProvider, ApolloClient, InMemoryCache } from "@apollo/client";
import { BrowserRouter, Route, Routes } from "react-router-dom";
import Header from "@/components/Header/Header";
import { ProductList } from "@/pages/ProductList/ProductList";
import { ProductDetail } from "@/pages/ProductDetail/ProductDetail";
import { AppState, Product, SelectedAttribute } from "@/types";

import "./index.css";

const client = new ApolloClient({
  uri: import.meta.env.VITE_GRAPHQL_URL,
  cache: new InMemoryCache(),
});

class App extends Component<Record<string, never>, AppState> {
  constructor(props: Record<string, never>) {
    super(props);
    this.state = {
      cartItems: [],
      isCartOpen: false,
      selectedCurrency: "$",
      currentCategory: "all",
    };
  }

  componentDidUpdate(_prevProps: Record<string, never>, prevState: AppState) {
    if (prevState.isCartOpen !== this.state.isCartOpen) {
      document.body.classList.toggle("prevent-scroll", this.state.isCartOpen);
    }
  }

  componentWillUnmount() {
    document.body.classList.remove("prevent-scroll");
  }

  toggleCart = () => {
    this.setState((prevState) => ({
      isCartOpen: !prevState.isCartOpen,
    }));
  };

  addToCart = (product: Product, selectedAttributes: SelectedAttribute[]) => {
    this.setState((prevState) => {
      const existingItemIndex = prevState.cartItems.findIndex(
        (item) =>
          item.id === product.id &&
          JSON.stringify(item.selectedAttributes) ===
            JSON.stringify(selectedAttributes)
      );

      let newCartItems;
      if (existingItemIndex > -1) {
        newCartItems = [...prevState.cartItems];
        newCartItems[existingItemIndex] = {
          ...newCartItems[existingItemIndex],
          quantity: newCartItems[existingItemIndex].quantity + 1,
        };
      } else {
        newCartItems = [
          ...prevState.cartItems,
          {
            ...product,
            selectedAttributes,
            quantity: 1,
          },
        ];
      }

      return {
        ...prevState,
        cartItems: newCartItems,
      };
    });
  };

  removeFromCart = (
    productId: string,
    selectedAttributes: SelectedAttribute[]
  ) => {
    this.setState((prevState) => {
      const existingItemIndex = prevState.cartItems.findIndex(
        (item) =>
          item.id === productId &&
          JSON.stringify(item.selectedAttributes) ===
            JSON.stringify(selectedAttributes)
      );

      if (existingItemIndex > -1) {
        const updatedItems = [...prevState.cartItems];
        const currentQuantity = updatedItems[existingItemIndex].quantity;

        if (currentQuantity === 1) {
          updatedItems.splice(existingItemIndex, 1);
        } else {
          updatedItems[existingItemIndex] = {
            ...updatedItems[existingItemIndex],
            quantity: currentQuantity - 1,
          };
        }

        return {
          ...prevState,
          cartItems: updatedItems,
        };
      }

      return prevState;
    });
  };

  setCategory = (category: string) => {
    this.setState({ currentCategory: category });
  };

  setCurrency = (currency: string) => {
    this.setState({ selectedCurrency: currency });
  };

  render() {
    const { isCartOpen, currentCategory, cartItems, selectedCurrency } =
      this.state;

    return (
      <ApolloProvider client={client}>
        <BrowserRouter>
          <div>
            <Header
              currentCategory={currentCategory}
              cartItems={cartItems}
              cartItemsCount={cartItems.reduce(
                (acc: number, item) => acc + item.quantity,
                0
              )}
              isCartOpen={isCartOpen}
              selectedCurrency={selectedCurrency}
              onCategoryChange={this.setCategory}
              onCartToggle={this.toggleCart}
              onAddItem={this.addToCart}
              onRemoveItem={this.removeFromCart}
            />
            <main className="container mx-auto pt-40 pb-10">
              {isCartOpen && (
                <div
                  className="fixed top-20 left-0 w-full h-[calc(100%-80px)] bg-[#39374838] backdrop-blur-xs z-10"
                  onClick={this.toggleCart}
                />
              )}
              <Routes>
                <Route
                  path="/"
                  element={
                    <ProductList
                      selectedCurrency={selectedCurrency}
                      onQuickShop={this.addToCart}
                    />
                  }
                />
                <Route
                  path="/:category"
                  element={
                    <ProductList
                      selectedCurrency={selectedCurrency}
                      onQuickShop={this.addToCart}
                    />
                  }
                />
                <Route
                  path="/product/:id"
                  element={
                    <ProductDetail
                      selectedCurrency={selectedCurrency}
                      onAddToCart={this.addToCart}
                    />
                  }
                />
                <Route
                  path="*"
                  element={
                    <ProductList
                      selectedCurrency={selectedCurrency}
                      onQuickShop={this.addToCart}
                    />
                  }
                />
              </Routes>
            </main>
          </div>
        </BrowserRouter>
      </ApolloProvider>
    );
  }
}

export default App;
