import { Component } from "react";
import { OperationVariables } from "@apollo/client";
import { GET_CATEGORY } from "@/graphql/queries";
import ProductCard from "@/components/Product/ProductCard";
import { LoadingSpinner, ErrorMessage } from "@/components/common";
import { handleLoadingStates, isNetworkError } from "@/utils/errorHandling";
import withRouterParams from "@/hocs/withRouterParams"; // Import HOC
import withApolloQuery, { WithApolloQueryProps } from "@/hocs/withApolloQuery"; // Import HOC and props type

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

// Define the type for the variables used in the GET_CATEGORY query
interface GetCategoryVariables extends OperationVariables {
  name: string;
}

// Define the props for the base component, including injected props from HOCs
type ProductListBaseProps = ProductListProps &
  WithApolloQueryProps<CategoryData, GetCategoryVariables> & {
    params: { category?: string }; // Injected by withRouterParams
  };

// Base class component remains largely the same, but uses the new props type
class ProductListBase extends Component<ProductListBaseProps> {
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

// Define options for withApolloQuery to map router params to query variables
const apolloOptions = {
  variables: (props: ProductListProps & { params: { category?: string } }) => ({
    name: props.params.category || "all",
  }),
};

// Compose the HOCs:
// 1. Wrap with withRouterParams to get URL parameters
// 2. Wrap the result with withApolloQuery to fetch data based on those parameters
export const ProductList = withRouterParams(
  withApolloQuery<
    ProductListProps & { params: { category?: string } }, // Props expected by the component being wrapped (ProductListBase + params)
    CategoryData, // Type of data returned by the query
    GetCategoryVariables // Type of variables for the query
  >(
    GET_CATEGORY,
    apolloOptions
  )(ProductListBase)
);

export default ProductList;
