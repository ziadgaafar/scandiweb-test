import { Component, ErrorInfo, ReactNode } from "react";

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return {
      hasError: true,
      error,
    };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error("Uncaught error:", error, errorInfo);
  }

  public resetError = () => {
    this.setState({
      hasError: false,
      error: null,
    });
    window.location.href = "/";
  };

  public render() {
    if (this.state.hasError) {
      return (
        <div className="flex flex-col items-center justify-center min-h-screen p-6 text-center bg-[#f8f8f8] animate-fade-in">
          <svg
            className="w-16 h-16 mb-6 text-error animate-[scale-in_0.5s_var(--ease-default)]"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-11v6h2v-6h-2zm0-4v2h2V7h-2z"
              fill="currentColor"
            />
          </svg>
          <p className="text-xl leading-[1.6] text-text mb-8 max-w-[600px]">
            Something went wrong. Please try again.
          </p>
          <button
            className="px-8 py-4 bg-primary text-background font-semibold text-base rounded transition-all duration-300 hover:-translate-y-0.5 hover:shadow-primary active:translate-y-0 animate-[slide-up_0.5s_var(--ease-default)_0.3s_backwards]"
            onClick={this.resetError}
          >
            Return to Home
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
