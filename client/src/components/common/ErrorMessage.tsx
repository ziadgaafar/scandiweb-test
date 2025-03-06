import { Component } from "react";
import { ApolloError } from "@apollo/client";
import { getErrorMessage } from "@/utils/errorHandling";

interface ErrorMessageProps {
  message: string | ApolloError | Error;
  actionText?: string;
  onAction?: () => void;
}

class ErrorMessage extends Component<ErrorMessageProps> {
  render() {
    const { message, actionText, onAction } = this.props;

    return (
      <div className="flex flex-col items-center justify-center p-6 min-h-[200px] text-center">
        <svg
          className="w-12 h-12 mb-4 text-error"
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
        <p className="text-base leading-[160%] text-text mb-4">
          {typeof message === "string" ? message : getErrorMessage(message)}
        </p>
        {actionText && onAction && (
          <button
            className="px-6 py-3 bg-primary text-background font-semibold text-sm uppercase transition-opacity duration-300 hover:opacity-80"
            onClick={onAction}
          >
            {actionText}
          </button>
        )}
      </div>
    );
  }
}

export default ErrorMessage;
