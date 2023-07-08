export const trimAndNull = (string: string | null | undefined) => {
  if (typeof string !== 'undefined' && string !== null) {
    string = string.trim();

    if (string.length === 0) {
      string = null;
    }

    return string;
  }

  return null;
};
