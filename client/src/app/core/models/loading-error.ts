export interface UnknownLoadingError {
  type: 'UNKNOWN_ERROR';
  error: Error;
}

export interface HttpLoadingError {
  type: 'HTTP_ERROR';
  error: Error;
  httpError: {
    status: number;
    data: unknown;
    message: string;
  };
}

export type LoadingError = UnknownLoadingError | HttpLoadingError;
