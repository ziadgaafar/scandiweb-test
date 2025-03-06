import { Component } from "react";
import { useParams } from "react-router-dom";
import { useQuery, ApolloError } from "@apollo/client";
import { GET_CATEGORY } from "@/graphql/queries";
import ProductCard from "@/components/Product/ProductCard";
import { LoadingSpinner, ErrorMessage } from "@/components/common";
import { handleLoadingStates, isNetworkError } from "@/utils/errorHandling";

interface ProductListProps {
  selectedCurrency: string;
  onQuickShop: (
    product: Product,
    attributes: { id: string; value: string }[]
  ) => void;
}

interface AttributeItem {
  id: string;
  value: string;
  displayValue: string;
}

interface ProductAttribute {
  id: string;
  name: string;
  type: string;
  items: AttributeItem[];
}

interface Product {
  id: string;
  name: string;
  brand: string;
  gallery: string[];
  inStock: boolean;
  attributes: ProductAttribute[];
  prices: Array<{
    amount: number;
    currency: {
      label: string;
      symbol: string;
    };
  }>;
}

interface CategoryData {
  category: {
    name: string;
    products: Product[];
  };
}

// Since we can't use hooks in class components, we need a wrapper
const withRouter = (
  Component: React.ComponentType<
    ProductListProps & {
      params: { category?: string };
      loading: boolean;
      error?: ApolloError;
      data?: CategoryData;
    }
  >
) => {
  return (props: Omit<ProductListProps, "params">) => {
    const params = useParams();
    const { loading, error, data } = useQuery<CategoryData>(GET_CATEGORY, {
      variables: { name: params.category || "all" },
    });

    return (
      <Component
        {...props}
        params={params}
        loading={loading}
        error={error}
        data={data}
      />
    );
  };
};

class ProductListBase extends Component<
  ProductListProps & {
    params: { category?: string };
    loading: boolean;
    error?: ApolloError;
    data?: CategoryData;
  }
> {
  handleQuickShop = (product: Product) => {
    const defaultAttributes =
      product.attributes?.length > 0
        ? product.attributes.map((attr: ProductAttribute) => ({
            id: attr.id,
            value: attr.items[0].value,
          }))
        : [];
    this.props.onQuickShop(product, defaultAttributes);
  };

  render() {
    const { loading, error, data, selectedCurrency } = this.props;

    const { isLoading, errorMessage } = handleLoadingStates(loading, error);

    if (isLoading) return <LoadingSpinner />;

    if (errorMessage) {
      const hasNetworkError = isNetworkError(error);
      return (
        <ErrorMessage
          message={errorMessage}
          actionText={hasNetworkError ? "Retry" : "Go to all products"}
          onAction={() => {
            if (hasNetworkError) {
              window.location.reload();
            } else {
              window.location.href = "/";
            }
          }}
        />
      );
    }

    if (!data?.category) {
      return (
        <ErrorMessage
          message="No products found in this category."
          actionText="Go to all products"
          onAction={() => {
            if (this.props.params.category !== "all") {
              window.location.href = "/";
            }
          }}
        />
      );
    }

    const { name, products } = data.category;

    return (
      <div>
        <h1 className="text-5xl font-normal mb-10 capitalize">{name}</h1>
        <div className="grid grid-cols-4 gap-10 mt-10">
          {products.map((product) => (
            <ProductCard
              key={product.id}
              {...product}
              selectedCurrency={selectedCurrency}
              onQuickShop={() => this.handleQuickShop(product)}
            />
          ))}
        </div>
      </div>
    );
  }
}

export const ProductList = withRouter(ProductListBase);
export default ProductList;
