export interface Cookbook {
  id: number;
  name: string;
  created_at: number;
  updated_at: number;
}

export interface CookbookWithCounts extends Cookbook {
  recipes_count: number;
  users_count: number;
}
