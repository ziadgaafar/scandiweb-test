import React from "react";
import {
  useQuery,
  DocumentNode,
  OperationVariables,
  QueryResult,
} from "@apollo/client";
import LoadingSpinner from "@/components/common/LoadingSpinner"; // Assuming you have this
import ErrorMessage from "@/components/common/ErrorMessage"; // Assuming you have this

/**
 * Props injected by the withApolloQuery HOC.
 * Includes the standard Apollo QueryResult fields.
 *
 * @template TData - The type of the data returned by the query.
 * @template TVariables - The type of the variables used by the query.
 */
export interface WithApolloQueryProps<
  TData = any,
  TVariables extends OperationVariables = OperationVariables
> extends Pick<QueryResult<TData, TVariables>, "data" | "loading" | "error"> {}

/**
 * Options for the withApolloQuery HOC.
 *
 * @template P - The original props of the wrapped component.
 * @template TVariables - The type of the variables used by the query.
 */
interface WithApolloQueryOptions<P, TVariables extends OperationVariables> {
  /**
   * An optional function to map the component's original props to query variables.
   *
   * @param {P} props - The original props of the wrapped component.
   * @returns {TVariables} The variables to be used for the query.
   */
  variables?: (props: P) => TVariables;
}

/**
 * Higher-Order Component (HOC) that wraps a component with Apollo Client's `useQuery`.
 * It fetches data using the provided GraphQL query and injects `data`, `loading`,
 * and `error` props into the wrapped component. It also handles rendering
 * loading and error states.
 *
 * @template P - The original props of the wrapped component.
 * @template TData - The type of the data returned by the query.
 * @template TVariables - The type of the variables used by the query.
 * @param {DocumentNode} query - The GraphQL query document (e.g., gql`...`).
 * @param {WithApolloQueryOptions<P, TVariables>} [options] - Optional configuration, like a function to map props to variables.
 * @returns {(WrappedComponent: React.ComponentType<P & WithApolloQueryProps<TData, TVariables>>) => React.FC<Omit<P, keyof WithApolloQueryProps<TData, TVariables>>>} A function that takes the component to wrap and returns the new HOC.
 */
const withApolloQuery =
  <
    P extends object,
    TData = any,
    TVariables extends OperationVariables = OperationVariables
  >(
    query: DocumentNode,
    options?: WithApolloQueryOptions<P, TVariables>
  ) =>
  (
    WrappedComponent: React.ComponentType<
      P & WithApolloQueryProps<TData, TVariables>
    >
  ): React.FC<Omit<P, keyof WithApolloQueryProps<TData, TVariables>>> => {
    /**
     * The inner functional component that executes the query and renders the wrapped component.
     */
    const ComponentWithApolloQuery: React.FC<
      Omit<P, keyof WithApolloQueryProps<TData, TVariables>>
    > = (props) => {
      // Determine variables based on the options and current props
      const queryVariables = options?.variables
        ? options.variables(props as P) // Pass original props to variables function
        : undefined;

      // Execute the query
      const { data, loading, error } = useQuery<TData, TVariables>(query, {
        variables: queryVariables,
      });

      // Handle loading state
      if (loading) {
        return <LoadingSpinner />;
      }

      // Handle error state
      if (error) {
        console.error("GraphQL Query Error:", error);
        return <ErrorMessage message={error.message} />;
      }

      // Render the wrapped component with injected props
      // Type assertion needed because Omit doesn't perfectly align
      return (
        <WrappedComponent
          {...(props as P)} // Pass original props
          data={data}
          loading={loading} // Although handled above, pass it for potential use in component
          error={error} // Although handled above, pass it for potential use in component
        />
      );
    };

    // Set a display name for easier debugging
    ComponentWithApolloQuery.displayName = `WithApolloQuery(${
      WrappedComponent.displayName || WrappedComponent.name || "Component"
    })`;

    return ComponentWithApolloQuery;
  };

export default withApolloQuery;
