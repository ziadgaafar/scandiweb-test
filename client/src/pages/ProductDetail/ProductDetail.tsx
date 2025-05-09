import { Component } from "react";
import { OperationVariables } from "@apollo/client"; // Import OperationVariables
import { GET_PRODUCT } from "@/graphql/queries";
import { Product, SelectedAttribute } from "@/types";
import { LoadingSpinner, ErrorMessage } from "@/components/common";
import { handleLoadingStates, isNetworkError } from "@/utils/errorHandling";
import { formatPrice } from "@/utils/currency";
import parse from "html-react-parser";
import withRouterParams from "@/hocs/withRouterParams"; // Import HOC
import withApolloQuery, { WithApolloQueryProps } from "@/hocs/withApolloQuery"; // Import HOC and props type

interface ProductDetailProps {
  selectedCurrency: string;
  onAddToCart: (
    product: Product,
    selectedAttributes: SelectedAttribute[]
  ) => void;
}

// Define the shape of the data returned by the GET_PRODUCT query
interface GetProductData {
  product: Product;
}

// Define the type for the variables used in the GET_PRODUCT query
interface GetProductVariables extends OperationVariables {
  id?: string; // id can be undefined if not in URL yet
}

interface ProductDetailState {
  selectedAttributes: { [key: string]: string };
  selectedImageIndex: number;
}

// Define the props for the base component, including injected props from HOCs
type ProductDetailBaseProps = ProductDetailProps &
  WithApolloQueryProps<GetProductData, GetProductVariables> & {
    params: { id?: string }; // Injected by withRouterParams
  };

// Base class component uses the new props type
class ProductDetailBase extends Component<
  ProductDetailBaseProps,
  ProductDetailState
> {
  constructor(props: ProductDetailBaseProps) {
    super(props);
    this.state = {
      selectedAttributes: {},
      selectedImageIndex: 0,
    };
  }

  areAllAttributesSelected = () => {
    const { data } = this.props;
    if (!data?.product?.attributes) return true;
    return data.product.attributes.every(
      (attr) => this.state.selectedAttributes[attr.id] !== undefined
    );
  };

  handleAttributeChange = (attributeId: string, value: string) => {
    this.setState((prevState) => ({
      selectedAttributes: {
        ...prevState.selectedAttributes,
        [attributeId]: value,
      },
    }));
  };

  handleImageSelect = (index: number) => {
    this.setState({ selectedImageIndex: index });
  };

  handlePreviousImage = () => {
    this.setState((prevState) => ({
      selectedImageIndex: Math.max(0, prevState.selectedImageIndex - 1),
    }));
  };

  handleNextImage = () => {
    const { data } = this.props;
    if (!data?.product?.gallery) return;

    this.setState((prevState) => ({
      selectedImageIndex: Math.min(
        data.product.gallery.length - 1,
        prevState.selectedImageIndex + 1
      ),
    }));
  };

  handleAddToCart = () => {
    const { data, onAddToCart } = this.props;
    const { selectedAttributes } = this.state;

    if (data?.product) {
      const attributesArray = Object.entries(selectedAttributes).map(
        ([id, value]) => ({ id, value })
      );
      onAddToCart(data.product, attributesArray);
    }
  };
  render() {
    const { loading, error, data } = this.props;

    const { isLoading, errorMessage } = handleLoadingStates(loading, error);

    if (isLoading) return <LoadingSpinner />;
    if (errorMessage) {
      const hasNetworkError = isNetworkError(error);
      return (
        <ErrorMessage
          message={errorMessage}
          actionText={hasNetworkError ? "Retry" : "Return to Home"}
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

    if (!data?.product) {
      return (
        <ErrorMessage
          message="Product not found."
          actionText="Return to Home"
          onAction={() => {
            window.location.href = "/";
          }}
        />
      );
    }

    const { product } = data;

    return (
      <div className="grid grid-cols-1 md:grid-cols-2 gap-[100px]">
        <div className="ml-10 gap-x-2 flex" data-testid="product-gallery">
          <div className="flex gap-10">
            <div className="flex flex-col gap-8 max-h-[511px] overflow-y-auto">
              {product.gallery.map((image, index) => (
                <div
                  key={index}
                  className={`size-20 cursor-pointer border border-transparent transition-all duration-300 relative overflow-hidden
                  hover:-translate-y-0.5 hover:shadow-sm hover:not-active:img:scale-110
                  ${
                    index === this.state.selectedImageIndex
                      ? "border-primary after:absolute after:inset-0 after:bg-black/10"
                      : ""
                  }`}
                  onClick={() => this.handleImageSelect(index)}
                >
                  <img
                    src={image}
                    alt={`${product.name} ${index + 1}`}
                    className="w-full h-full object-contain transition-transform duration-300"
                  />
                </div>
              ))}
            </div>
          </div>
          <div className="w-[610px] h-[511px] relative overflow-hidden rounded group">
            <img
              src={product.gallery[this.state.selectedImageIndex]}
              alt={product.name}
              className="w-full h-full object-contain transition-transform duration-500 hover:scale-105"
            />
            {product.gallery.length > 1 && (
              <>
                <button
                  onClick={this.handlePreviousImage}
                  className="cursor-pointer disabled:cursor-default absolute left-4 top-1/2 -translate-y-1/2 bg-black/75 hover:bg-black text-white p-2 transition-all duration-300 opacity-0 group-hover:opacity-100 disabled:opacity-0"
                  disabled={this.state.selectedImageIndex === 0}
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={2}
                    stroke="currentColor"
                    className="w-6 h-6"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M15.75 19.5L8.25 12l7.5-7.5"
                    />
                  </svg>
                </button>
                <button
                  onClick={this.handleNextImage}
                  className="cursor-pointer disabled:cursor-default absolute right-4 top-1/2 -translate-y-1/2 bg-black/75 hover:bg-black text-white p-2 transition-all duration-300 opacity-0 group-hover:opacity-100 disabled:opacity-0"
                  disabled={
                    this.state.selectedImageIndex === product.gallery.length - 1
                  }
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth={2}
                    stroke="currentColor"
                    className="w-6 h-6"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M8.25 4.5l7.5 7.5-7.5 7.5"
                    />
                  </svg>
                </button>
              </>
            )}
          </div>
        </div>
        <div className="flex flex-col w-fit gap-y-12 max-w-md">
          <h2 className="font-semibold text-3xl leading-7 mb-1 text-text">
            {product.name}
          </h2>
          <div className="flex flex-col gap-y-6">
            {product.attributes?.length > 0 &&
              product.attributes.map((attribute) => (
                <div
                  key={attribute.id}
                  className="mb-6"
                  data-testid={`product-attribute-${attribute.name.toLowerCase()}`}
                >
                  <h3 className="uppercase font-bold text-lg leading-4 mb-2">
                    {attribute.name}:
                  </h3>
                  <div className="flex flex-wrap gap-3">
                    {attribute.items.map((item) => (
                      <button
                        key={item.id}
                        className={`relative overflow-hidden text-base transition-all duration-300 cursor-pointer
                        ${
                          attribute.type === "swatch"
                            ? "size-8 p-0.5 border border-border hover:not-selected:translate-y-[-2px] hover:not-selected:scale-110"
                            : "w-20 h-10 flex items-center justify-center border border-text bg-transparent hover:not-selected:translate-y-[-2px] hover:not-selected:shadow-sm before:content-[''] before:absolute before:top-0 before:left-[-100%] before:w-full before:h-full before:bg-primary/10 hover:not-selected:before:translate-x-full before:transition-transform before:duration-300"
                        }
                        ${
                          this.state.selectedAttributes[attribute.id] ===
                          item.value
                            ? attribute.type === "swatch"
                              ? "outline outline-primary outline-offset-1 border-primary scale-110"
                              : "!bg-text text-background border-text scale-105"
                            : ""
                        }`}
                        style={
                          attribute.type === "swatch"
                            ? { backgroundColor: item.value }
                            : undefined
                        }
                        onClick={() =>
                          this.handleAttributeChange(attribute.id, item.value)
                        }
                        data-testid={`product-attribute-${attribute.name.toLowerCase()}-${
                          item.value
                        }`}
                      >
                        {attribute.type !== "swatch" && item.value}
                      </button>
                    ))}
                  </div>
                </div>
              ))}
            <div className="">
              <span className="font-bold text-lg mb-4 block uppercase">
                PRICE:
              </span>
              <span className="font-bold text-2xl leading-4 text-text">
                {formatPrice(product.prices, this.props.selectedCurrency)}
              </span>
            </div>
            <button
              className="cursor-pointer w-full h-[52px] bg-primary text-background font-semibold text-base uppercase transition-all duration-300 relative overflow-hidden before:content-[''] before:absolute before:top-0 before:left-[-100%] before:w-full before:h-full before:bg-gradient-to-r before:from-transparent before:via-white/30 before:to-transparent before:transition-all before:duration-600 hover:not-disabled:before:left-full hover:not-disabled:-translate-y-0.5 hover:not-disabled:shadow-primary disabled:opacity-50 disabled:cursor-not-allowed"
              data-testid="add-to-cart"
              disabled={!product.inStock || !this.areAllAttributesSelected()}
              onClick={this.handleAddToCart}
            >
              {product.inStock ? "ADD TO CART" : "OUT OF STOCK"}
            </button>
          </div>
          <div
            data-testid="product-description"
            className="font-normal text-base text-text"
          >
            {parse(product.description || "")}
          </div>
        </div>
      </div>
    );
  }
}

// Define options for withApolloQuery to map router params to query variables
const apolloOptions = {
  variables: (props: ProductDetailProps & { params: { id?: string } }) => ({
    id: props.params.id, // Get id from router params
  }),
  // Skip the query if the id param is not available yet
  skip: (props: ProductDetailProps & { params: { id?: string } }) =>
    !props.params.id,
};

// Compose the HOCs:
// 1. Wrap with withRouterParams to get URL parameters (product id)
// 2. Wrap the result with withApolloQuery to fetch product data based on the id
export const ProductDetail = withRouterParams(
  withApolloQuery<
    ProductDetailProps & { params: { id?: string } }, // Props expected by the component being wrapped
    GetProductData, // Type of data returned by the query
    GetProductVariables // Type of variables for the query
  >(
    GET_PRODUCT,
    apolloOptions
  )(ProductDetailBase)
);

export default ProductDetail;
