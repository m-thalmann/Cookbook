import { Recipe } from './api/ApiInterfaces';

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
    .replace(/-+/gm, '-')
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

/**
 * Calculates the total time needed for this recipe
 *
 * @param recipe the recipe
 * @returns The total time string
 */
export function calculateTotalTime(recipe: Recipe | null) {
  let time = 0;

  if (recipe?.preparationTime) {
    time += recipe.preparationTime;
  }
  if (recipe?.restTime) {
    time += recipe.restTime;
  }
  if (recipe?.cookTime) {
    time += recipe.cookTime;
  }

  if (time > 0) {
    return time + ' min';
  } else {
    return null;
  }
}

type LoggerColor = 'red' | 'blue' | 'lightblue' | 'orange' | 'green';

function getLoggerColor(color: LoggerColor) {
  switch (color) {
    case 'red':
      return '#d10000';
    case 'blue':
      return '#21f';
    case 'lightblue':
      return '#0068ad';
    case 'orange':
      return '#c97600';
    case 'green':
      return '#009100';
  }
}

/**
 * Provides styled logging functions
 */
export const Logger = {
  log: (name: string, color: LoggerColor, ...message: any[]) => {
    Logger.out('log', name, color, ...message);
  },
  info: (name: string, color: LoggerColor, ...message: any[]) => {
    Logger.out('info', name, color, ...message);
  },
  warn: (name: string, color: LoggerColor, ...message: any[]) => {
    Logger.out('warn', name, color, ...message);
  },
  error: (name: string, color: LoggerColor, ...message: any[]) => {
    Logger.out('error', name, color, ...message);
  },
  out: (type: 'log' | 'info' | 'warn' | 'error', name: string, color: LoggerColor, ...message: any[]) => {
    console[type](`%c ${name} `, `color: #fff; background: ${getLoggerColor(color)}; border-radius: 2px`, ...message);
  },
};
