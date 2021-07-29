export class ApiResponse<T> {
  /**
   * @param status the http status
   * @param value the received value, or null if an error occured
   */
  constructor(
    public readonly status: number,
    public readonly value: T | null = null,
    public readonly error: string | null = null
  ) {}

  /**
   * @returns true, if the status-code is between 200 and 299
   */
  isOK() {
    return this.status >= 200 && this.status < 300;
  }

  /**
   * @returns true, if the status-code is 204
   */
  isNoContent() {
    return this.status == 204;
  }

  /**
   * @returns true, if the status-code is 400
   */
  isBadRequest() {
    return this.status == 400;
  }

  /**
   * @returns true, if the status-code is 401
   */
  isUnauthorized() {
    return this.status == 401;
  }

  /**
   * @returns true, if the status-code is 403
   */
  isForbidden() {
    return this.status == 403;
  }

  /**
   * @returns true, if the status-code is 404
   */
  isNotFound() {
    return this.status == 404;
  }

  /**
   * @returns true, if the status-code is 409
   */
  isConflict() {
    return this.status == 409;
  }

  /**
   * @returns true, if the status-code is >= 500
   */
  isServerError() {
    return this.status >= 500;
  }
}
