import { Component } from "react";

class LoadingSpinner extends Component {
  render() {
    return (
      <div className="flex justify-center items-center w-full">
        <div className="size-12 border-3 border-border border-t-primary rounded-full animate-spin"></div>
      </div>
    );
  }
}

export default LoadingSpinner;
