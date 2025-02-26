import { Component } from "react";
import "./LoadingSpinner.scss";

class LoadingSpinner extends Component {
  render() {
    return (
      <div className="loading-spinner-container">
        <div className="loading-spinner"></div>
      </div>
    );
  }
}

export default LoadingSpinner;
