import { Component } from "react";
import { useParams } from "react-router-dom";
import { useQuery, ApolloError } from "@apollo/client";
import { GET_PRODUCT } from "@/graphql/queries";
import { Product, SelectedAttribute } from "@/types";
import { LoadingSpinner, ErrorMessage } from "@/components/common";
import { handleLoadingStates, isNetworkError } from "@/utils/errorHandling";
import { formatPrice } from "@/utils/currency";
import parse from "html-react-parser";

interface ProductDetailProps {
  selectedCurrency: string;
  onAddToCart: (
    product: Product,
    selectedAttributes: SelectedAttribute[]
  ) => void;
}

const withRouter = (
  Component: React.ComponentType<
    ProductDetailProps & {
      params: { id?: string };
      loading: boolean;
      error?: ApolloError;
      data?: { product: Product };
    }
  >
) => {
  return (props: Omit<ProductDetailProps, "params">) => {
    const params = useParams();
    const { loading, error, data } = useQuery(GET_PRODUCT, {
      variables: { id: params.id },
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

interface ProductDetailState {
  selectedAttributes: { [key: string]: string };
  selectedImageIndex: number;
}

class ProductDetailBase extends Component<
  ProductDetailProps & {
    params: { id?: string };
    loading: boolean;
    error?: ApolloError;
    data?: { product: Product };
  },
  ProductDetailState
> {
  constructor(
    props: ProductDetailProps & {
      params: { id?: string };
      loading: boolean;
      error?: ApolloError;
      data?: { product: Product };
    }
  ) {
    super(props);
    this.state = {
      selectedAttributes: {},
      selectedImageIndex: 0,
    };
  }

  componentDidUpdate(prevProps: ProductDetailBase["props"]) {
    if (prevProps.data?.product?.id !== this.props.data?.product?.id) {
      this.setDefaultAttributes();
    }
  }

  componentDidMount() {
    this.setDefaultAttributes();
  }

  setDefaultAttributes = () => {
    const { data } = this.props;
    if (data?.product) {
      const defaultAttributes =
        data.product.attributes?.length > 0
          ? data.product.attributes.reduce(
              (acc, attr) => ({
                ...acc,
                [attr.id]: attr.items[0].value,
              }),
              {}
            )
          : {};
      this.setState({ selectedAttributes: defaultAttributes });
    }
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
          <div className="w-[610px] h-[511px] relative overflow-hidden rounded">
            <img
              src={product.gallery[this.state.selectedImageIndex]}
              alt={product.name}
              className="w-full h-full object-contain transition-transform duration-500 hover:scale-105"
            />
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
                  data-testid={`product-attribute-${attribute.id}`}
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
                        data-testid={`attribute-${attribute.id}-${item.value}`}
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
              disabled={!product.inStock}
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

export const ProductDetail = withRouter(ProductDetailBase);
export default ProductDetail;
