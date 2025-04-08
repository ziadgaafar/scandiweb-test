import React from "react";
import { useParams } from "react-router-dom";

/**
 * Higher-Order Component (HOC) that injects URL parameters into the wrapped component.
 * It uses the `useParams` hook from `react-router-dom` to retrieve the parameters
 * and passes them as a `params` prop.
 *
 * @template P - The original props of the wrapped component.
 * @param {React.ComponentType<P>} WrappedComponent - The component to wrap.
 * @returns {React.FC<Omit<P, 'params'>>} A new component that renders the WrappedComponent with the injected `params` prop.
 */
const withRouterParams = <P extends object>(
  WrappedComponent: React.ComponentType<
    P & { params: ReturnType<typeof useParams> }
  >
) => {
  /**
   * The inner functional component that uses the hook and renders the wrapped component.
   * It omits the 'params' prop from the original component's props definition
   * because this HOC provides it.
   */
  const ComponentWithRouterParams: React.FC<Omit<P, "params">> = (props) => {
    const params = useParams();
    // Type assertion needed because Omit doesn't perfectly align with the expected props of WrappedComponent
    return <WrappedComponent {...(props as P)} params={params} />;
  };

  // Set a display name for easier debugging in React DevTools
  ComponentWithRouterParams.displayName = `WithRouterParams(${
    WrappedComponent.displayName || WrappedComponent.name || "Component"
  })`;

  return ComponentWithRouterParams;
};

export default withRouterParams;
