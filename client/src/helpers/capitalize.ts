/**
 * Capitalize first letter of a string
 *
 * @param string
 */
export const capitalize = (string: string): string =>
  string.charAt(0).toUpperCase() + string.slice(1);
