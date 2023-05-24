import { Injectable } from '@angular/core';
import { DefaultTranspiler, HashMap, Translation, TranslocoTranspiler } from '@ngneat/transloco';

const PLURAL_MATCH = /^\[\[ (\w+(?:,(?:\\.|[^,])+)+) \]\]$/g;
const PLURAL_CASE_MATCH = /^([<>!=]=?|default)(\d+(?:\.\d*)?|) '((?:\\.|[^'])+)'$/g;
const SPLIT_BY_NON_ESCAPED_COMMA = /(\\.|[^,])+/g;

@Injectable()
export class CustomTranspiler extends DefaultTranspiler implements TranslocoTranspiler {
  override transpile(value: any, params: HashMap = {}, translation: Translation, key: string): any {
    if (typeof value === 'string') {
      const pluralMatch = [...value.matchAll(PLURAL_MATCH)][0]?.[1];

      if (pluralMatch) {
        return this.transpilePlural(pluralMatch.trim(), params, translation, key);
      }
    }

    return super.transpile(value, params, translation, key);
  }

  private transpilePlural(value: string, params: HashMap, translation: Translation, key: string): string {
    const [variableKey, ...pluralCases] = value
      .match(SPLIT_BY_NON_ESCAPED_COMMA)!
      .map((c) => c.replace(/\\,/g, ',').trim());

    const variable = params[variableKey] ?? 0;

    for (let i = 0; i < pluralCases.length; i++) {
      const pluralCaseString = pluralCases[i];

      try {
        const pluralCase = new PluralCase(pluralCaseString);

        if (!pluralCase.isMatching(variable)) {
          continue;
        }

        return super.transpile(pluralCase.translationString, params, translation, key);
      } catch (e) {
        console.warn(`Transloco Custom Transpiler: Error in plural case "${pluralCaseString}": ${e}`);
        break;
      }
    }

    return super.transpile(value, params, translation, key);
  }
}

class PluralCase {
  readonly operator: string;
  readonly amount: number;
  readonly translationString: string;

  constructor(caseString: string) {
    if (!caseString.match(PLURAL_CASE_MATCH)) {
      throw new Error('Invalid format');
    }

    const pluralCaseMatch = [...caseString.matchAll(PLURAL_CASE_MATCH)][0];

    const [_, operator, amountString, translationString] = pluralCaseMatch;

    this.operator = operator;
    this.amount = parseFloat(amountString);
    this.translationString = translationString.replace(/\\'/g, "'");
  }

  isMatching(amount: number): boolean {
    switch (this.operator) {
      case 'default':
        return true;
      case '<':
        return amount < this.amount;
      case '<=':
        return amount <= this.amount;
      case '>':
        return amount > this.amount;
      case '>=':
        return amount >= this.amount;
      case '=':
      case '==':
        return amount === this.amount;
      case '!=':
        return amount !== this.amount;
      default:
        throw new Error('Invalid operator');
    }
  }
}
