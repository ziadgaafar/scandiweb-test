import { Component } from "react";
import { ApolloError } from "@apollo/client";
import { getErrorMessage } from "../../utils/errorHandling";
import "./ErrorMessage.scss";

interface ErrorMessageProps {
  message: string | ApolloError | Error;
  actionText?: string;
  onAction?: () => void;
}

class ErrorMessage extends Component<ErrorMessageProps> {
  render() {
    const { message, actionText, onAction } = this.props;

    return (
      <div className="error-message">
        <svg
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-11v6h2v-6h-2zm0-4v2h2V7h-2z"
            fill="currentColor"
          />
        </svg>
        <p>
          {typeof message === "string" ? message : getErrorMessage(message)}
        </p>
        {actionText && onAction && (
          <button className="error-message__action" onClick={onAction}>
            {actionText}
          </button>
        )}
      </div>
    );
  }
}

export default ErrorMessage;
