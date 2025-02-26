import { ApolloError } from "@apollo/client";

export const getErrorMessage = (error: ApolloError | Error): string => {
  if (error instanceof ApolloError) {
    // Handle network errors
    if (error.networkError) {
      return "Network error: Unable to connect to the server. Please check your internet connection.";
    }

    // Handle GraphQL errors
    if (error.graphQLErrors?.length) {
      const messages = error.graphQLErrors.map((e) => e.message);
      return `GraphQL Error: ${messages.join(", ")}`;
    }
  }

  // Fallback error message
  return (
    error.message || "An unexpected error occurred. Please try again later."
  );
};

export const parseGraphQLError = (error: ApolloError | undefined): string => {
  if (!error) return "";

  if (error.networkError) {
    if ("statusCode" in error.networkError) {
      switch (error.networkError.statusCode) {
        case 404:
          return "The requested resource was not found.";
        case 500:
          return "Internal server error. Please try again later.";
        default:
          return `Server error: ${error.networkError.statusCode}`;
      }
    }
    return "Network error: Unable to connect to the server.";
  }

  if (error.graphQLErrors?.length) {
    return error.graphQLErrors[0].message;
  }

  return "An unexpected error occurred.";
};

export const isNetworkError = (error: ApolloError | undefined): boolean => {
  return !!error?.networkError;
};

export const handleLoadingStates = (loading: boolean, error?: ApolloError) => {
  if (loading) {
    return {
      isLoading: true,
      errorMessage: "",
    };
  }

  if (error) {
    return {
      isLoading: false,
      errorMessage: parseGraphQLError(error),
    };
  }

  return {
    isLoading: false,
    errorMessage: "",
  };
};

interface RetryConfig {
  maxAttempts?: number;
  delayMs?: number;
}

export const retryOperation = async <T>(
  operation: () => Promise<T>,
  config: RetryConfig = {}
): Promise<T> => {
  const { maxAttempts = 3, delayMs = 1000 } = config;
  let attempts = 0;
  let lastError: Error;

  while (attempts < maxAttempts) {
    try {
      return await operation();
    } catch (error) {
      lastError = error as Error;
      attempts++;
      if (attempts === maxAttempts) break;
      await new Promise((resolve) => setTimeout(resolve, delayMs));
    }
  }

  throw lastError!;
};
