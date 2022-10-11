export interface Ingredient {
  id: number;
  recipe_id: number;
  name: string;
  amount: number | null;
  unit: string | null;
  group: string | null;
  created_at: number;
  updated_at: number;
}
