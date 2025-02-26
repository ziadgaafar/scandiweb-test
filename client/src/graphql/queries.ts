import { gql } from "@apollo/client";

export const GET_CATEGORY = gql`
  query GetCategory($name: String!) {
    category(name: $name) {
      name
      products {
        id
        name
        brand
        inStock
        gallery
        description
        attributes {
          id
          name
          type
          items {
            id
            value
            displayValue
          }
        }
        prices {
          amount
          currency {
            label
            symbol
          }
        }
      }
    }
  }
`;

export const GET_PRODUCT = gql`
  query GetProduct($id: String!) {
    product(id: $id) {
      id
      name
      brand
      inStock
      gallery
      description
      attributes {
        id
        name
        type
        items {
          id
          value
          displayValue
        }
      }
      prices {
        amount
        currency {
          label
          symbol
        }
      }
    }
  }
`;
