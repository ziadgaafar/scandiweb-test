import { Component } from "react";
import { useParams } from "react-router-dom";
import { useQuery, ApolloError } from "@apollo/client";
import { GET_PRODUCT } from "../../graphql/queries";
import { Product, SelectedAttribute } from "../../types";
import { LoadingSpinner, ErrorMessage } from "../../components/common";
import { handleLoadingStates, isNetworkError } from "../../utils/errorHandling";
import "./ProductDetail.scss";
import { formatPrice } from "../../utils/currency";

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
      <div className="product-detail">
        <div className="product-detail__gallery" data-testid="product-gallery">
          <div className="product-detail__gallery-thumbnails">
            {product.gallery.map((image, index) => (
              <div
                key={index}
                className={`product-detail__gallery-thumbnail ${
                  index === this.state.selectedImageIndex ? "active" : ""
                }`}
                onClick={() => this.handleImageSelect(index)}
              >
                <img src={image} alt={`${product.name} ${index + 1}`} />
              </div>
            ))}
          </div>
          <div className="product-detail__gallery-main">
            <img
              src={product.gallery[this.state.selectedImageIndex]}
              alt={product.name}
            />
          </div>
        </div>
        <div className="product-detail__info">
          <h2 className="product-detail__brand">{product.brand}</h2>
          <div className="product-detail__price">
            <span className="product-detail__price-label">PRICE:</span>
            <span className="product-detail__price-amount">
              {formatPrice(product.prices, this.props.selectedCurrency)}
            </span>
          </div>
          <h1 className="product-detail__name">{product.name}</h1>
          {product.attributes?.length > 0 &&
            product.attributes.map((attribute) => (
              <div
                key={attribute.id}
                className="product-detail__attribute"
                data-testid={`product-attribute-${attribute.id}`}
              >
                <h3 className="product-detail__attribute-name">
                  {attribute.name}
                </h3>
                <div className="product-detail__attribute-values">
                  {attribute.items.map((item) => (
                    <button
                      key={item.id}
                      className={`product-detail__attribute-value ${
                        attribute.type === "swatch"
                          ? "product-detail__attribute-value--swatch"
                          : ""
                      } ${
                        this.state.selectedAttributes[attribute.id] ===
                        item.value
                          ? "selected"
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
          <div
            data-testid="product-description"
            className="product-detail__description"
            dangerouslySetInnerHTML={{ __html: product.description || "" }}
          />
          <button
            className="product-detail__add-to-cart"
            data-testid="add-to-cart"
            disabled={!product.inStock}
            onClick={this.handleAddToCart}
          >
            {product.inStock ? "ADD TO CART" : "OUT OF STOCK"}
          </button>
        </div>
      </div>
    );
  }
}

export const ProductDetail = withRouter(ProductDetailBase);
export default ProductDetail;
