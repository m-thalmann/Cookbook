/**
 * Generates a slug out of a string by:
 * - removing whitespace on start/end
 * - converting to lowercase
 * - replacing whitespaces with "-"
 * - removing all characters other than a-z, 0-9
 *
 * @param string the string to slugify
 *
 * @returns the slugified string
 */
export function slugify(string: string) {
  return string
    .trim()
    .toLowerCase()
    .replace(/\s+/gm, '-')
    .replace(/[^a-z0-9\-]/gm, '')
    .replace(/\-+/gm, '-')
    .replace(/-$/g, '');
}

/**
 * If the supplied string is not null it is trimmed
 * and if the length is 0 it is set to null
 *
 * @param string the string to trim and set null
 *
 * @returns the new string
 */
export function trimAndNull(string: string | null | undefined) {
  if (typeof string !== 'undefined' && string !== null) {
    string = string.trim();

    if (string.length === 0) {
      string = null;
    }
  }

  return string;
}
