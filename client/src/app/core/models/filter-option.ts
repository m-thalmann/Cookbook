export interface FilterOption {
  column: string;
  type?: FilterType;
  value: string | number | boolean | null;
}

export type FilterType = 'not' | 'like' | 'in' | 'notin' | 'lt' | 'le' | 'gt' | 'ge';
