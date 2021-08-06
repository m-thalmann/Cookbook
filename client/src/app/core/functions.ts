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
