import { Component } from "react";
import { Link } from "react-router-dom";
import CartOverlay from "@/components/Cart/CartOverlay";
import { CartItem, Product, SelectedAttribute } from "@/types";
import withApolloQuery, { WithApolloQueryProps } from "@/hocs/withApolloQuery"; // Import HOC and props type
import { GET_CATEGORIES } from "@/graphql/queries"; // Import the query
import { OperationVariables } from "@apollo/client"; // Import OperationVariables

// Define the expected shape of the category data
interface Category {
  name: string;
}

// Define the shape of the data returned by the GET_CATEGORIES query
interface GetCategoriesData {
  categories: Category[];
}

interface HeaderProps {
  currentCategory: string;
  cartItems: CartItem[];
  cartItemsCount: number;
  isCartOpen: boolean;
  selectedCurrency: string;
  onCategoryChange: (category: string) => void;
  onCartToggle: () => void;
  onAddItem: (
    product: Product,
    selectedAttributes: SelectedAttribute[]
  ) => void;
  onRemoveItem: (
    productId: string,
    selectedAttributes: SelectedAttribute[]
  ) => void;
  clearCart: () => void;
}

// Combine original props with HOC-injected props
type HeaderComponentProps = HeaderProps &
  WithApolloQueryProps<GetCategoriesData, OperationVariables>;

class Header extends Component<HeaderComponentProps> {
  renderCartOverlay() {
    const {
      cartItems,
      isCartOpen,
      selectedCurrency,
      onAddItem,
      onRemoveItem,
      clearCart,
    } = this.props;

    if (!isCartOpen) return null;

    return (
      <CartOverlay
        items={cartItems}
        selectedCurrency={selectedCurrency}
        onAddItem={onAddItem}
        onRemoveItem={onRemoveItem}
        clearCart={clearCart}
      />
    );
  }

  render() {
    // Destructure props including those from withApolloQuery
    const {
      currentCategory,
      cartItemsCount,
      onCartToggle,
      data, // Data from the HOC
      // loading and error are handled by the HOC, but available if needed
    } = this.props;

    // Extract categories from data, provide default empty array
    const categories = data?.categories ?? [];

    return (
      <header className="h-20 fixed inset-x-0 top-0 bg-background z-100">
        <div className="container mx-auto h-full flex items-center justify-between">
          <nav className="flex gap-8 h-full items-center">
            {/* Map over fetched categories */}
            {categories.map((category) => (
              <Link
                key={category.name}
                to={`/${category.name}`} // Use category.name for the link
                className={`h-full flex items-center text-base uppercase text-text border-b-2 border-transparent transition-all duration-300 hover:text-primary hover:border-primary ${
                  currentCategory === category.name // Compare with category.name
                    ? "!border-primary !text-primary"
                    : ""
                }`}
                data-testid={
                  currentCategory === category.name // Compare with category.name
                    ? "active-category-link"
                    : "category-link"
                }
                onClick={() => this.props.onCategoryChange(category.name)} // Pass category.name
              >
                {category.name.toUpperCase()} {/* Display category.name */}
              </Link>
            ))}
          </nav>

          <Link to="/" onClick={() => this.props.onCategoryChange("all")}>
            <svg
              className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
              width="33"
              height="31"
              viewBox="0 0 33 31"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M30.0222 23.6646C30.0494 23.983 29.8009 24.2566 29.4846 24.2566H3.46924C3.15373 24.2566 2.90553 23.9843 2.93156 23.6665L4.7959 0.912269C4.8191 0.629618 5.05287 0.412109 5.33372 0.412109H27.5426C27.8226 0.412109 28.0561 0.628527 28.0801 0.910361L30.0222 23.6646Z"
                fill="#1DCF65"
              />
              <path
                d="M32.0988 29.6014C32.1313 29.9985 31.8211 30.339 31.4268 30.339H1.59438C1.2009 30.339 0.890922 30.0002 0.922082 29.6037L3.06376 2.34718C3.09168 1.9927 3.38426 1.71973 3.73606 1.71973H29.1958C29.5468 1.71973 29.8391 1.99161 29.868 2.34499L32.0988 29.6014Z"
                fill="url(#paint0_linear_150_362)"
              />
              <path
                d="M15.9232 21.6953C12.0402 21.6953 8.88135 17.8631 8.88135 13.1528C8.88135 12.9075 9.07815 12.7085 9.32109 12.7085C9.56403 12.7085 9.76084 12.9073 9.76084 13.1528C9.76084 17.3732 12.5253 20.8067 15.9234 20.8067C19.3214 20.8067 22.0859 17.3732 22.0859 13.1528C22.0859 12.9075 22.2827 12.7085 22.5257 12.7085C22.7686 12.7085 22.9654 12.9073 22.9654 13.1528C22.9653 17.8631 19.8062 21.6953 15.9232 21.6953Z"
                fill="white"
              />
              <path
                d="M20.2581 13.0337C20.1456 13.0337 20.0331 12.9904 19.9471 12.9036C19.7754 12.7301 19.7754 12.4488 19.9471 12.2753L22.226 9.97292C22.3084 9.88966 22.4203 9.84277 22.5369 9.84277C22.6536 9.84277 22.7654 9.88952 22.8479 9.97292L25.1045 12.2529C25.2762 12.4264 25.2762 12.7077 25.1045 12.8812C24.9327 13.0546 24.6543 13.0547 24.4826 12.8812L22.5368 10.9155L20.569 12.9036C20.4831 12.9904 20.3706 13.0337 20.2581 13.0337Z"
                fill="white"
              />
              <defs>
                <linearGradient
                  id="paint0_linear_150_362"
                  x1="25.8733"
                  y1="26.3337"
                  x2="7.51325"
                  y2="4.9008"
                  gradientUnits="userSpaceOnUse"
                >
                  <stop stopColor="#52D67A" />
                  <stop offset="1" stopColor="#5AEE87" />
                </linearGradient>
              </defs>
            </svg>
          </Link>

          <div className="flex items-center gap-[22px]">
            <button
              className="cursor-pointer relative flex items-center justify-center p-2 rounded-full transition-colors duration-300 hover:bg-black/5"
              onClick={onCartToggle}
              data-testid="cart-btn"
            >
              <svg
                width="20"
                height="19"
                viewBox="0 0 20 19"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M19.5613 3.87359C19.1822 3.41031 18.5924 3.12873 17.9821 3.12873H5.15889L4.75914 1.63901C4.52718 0.773016 3.72769 0.168945 2.80069 0.168945H0.653099C0.295301 0.168945 0 0.450523 0 0.793474C0 1.13562 0.294459 1.418 0.653099 1.418H2.80069C3.11654 1.418 3.39045 1.61936 3.47434 1.92139L6.04306 11.7077C6.27502 12.5737 7.07451 13.1778 8.00152 13.1778H16.4028C17.3289 13.1778 18.1507 12.5737 18.3612 11.7077L19.9405 5.50575C20.0877 4.941 19.9619 4.33693 19.5613 3.87365L19.5613 3.87359ZM18.6566 5.22252L17.0773 11.4245C16.9934 11.7265 16.7195 11.9279 16.4036 11.9279H8.00154C7.68569 11.9279 7.41178 11.7265 7.32789 11.4245L5.49611 4.39756H17.983C18.1936 4.39756 18.4042 4.49824 18.5308 4.65948C18.6567 4.81994 18.7192 5.0213 18.6567 5.22266L18.6566 5.22252Z"
                  fill="#43464E"
                />
                <path
                  d="M8.44437 13.9814C7.2443 13.9814 6.25488 14.9276 6.25488 16.0751C6.25488 17.2226 7.24439 18.1688 8.44437 18.1688C9.64445 18.1696 10.6339 17.2234 10.6339 16.0757C10.6339 14.928 9.64436 13.9812 8.44437 13.9812V13.9814ZM8.44437 16.9011C7.9599 16.9011 7.58071 16.5385 7.58071 16.0752C7.58071 15.6119 7.9599 15.2493 8.44437 15.2493C8.92885 15.2493 9.30804 15.6119 9.30804 16.0752C9.30722 16.5188 8.90748 16.9011 8.44437 16.9011Z"
                  fill="#43464E"
                />
                <path
                  d="M15.6875 13.9814C14.4875 13.9814 13.498 14.9277 13.498 16.0752C13.498 17.2226 14.4876 18.1689 15.6875 18.1689C16.8875 18.1689 17.877 17.2226 17.877 16.0752C17.8565 14.9284 16.8875 13.9814 15.6875 13.9814ZM15.6875 16.9011C15.2031 16.9011 14.8239 16.5385 14.8239 16.0752C14.8239 15.612 15.2031 15.2493 15.6875 15.2493C16.172 15.2493 16.5512 15.612 16.5512 16.0752C16.5512 16.5188 16.1506 16.9011 15.6875 16.9011Z"
                  fill="#43464E"
                />
              </svg>

              {cartItemsCount > 0 && (
                <span className="absolute -top-2 -right-2 min-w-[20px] h-5 px-1.5 flex items-center justify-center bg-text text-background rounded-full text-sm font-bold">
                  {cartItemsCount}
                </span>
              )}
            </button>
          </div>
          {this.renderCartOverlay()}
        </div>
      </header>
    );
  }
}

// Wrap the component with the HOC, providing the query
export default withApolloQuery<HeaderProps, GetCategoriesData>(GET_CATEGORIES)(
  Header
);
