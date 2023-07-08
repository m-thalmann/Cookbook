export class HandledError extends Error {
  constructor(public readonly error: unknown) {
    super('This error was handled. Check the `error` property for the actual error');
    this.name = 'HandledError';
  }
}
