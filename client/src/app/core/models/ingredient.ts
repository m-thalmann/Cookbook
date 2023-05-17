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

export interface SimpleIngredient {
  name: string;
  unit: string | null;
}

export interface CreateIngredientData {
  name: string;
  amount?: number | null;
  unit?: string | null;
  group?: string | null;
}

export interface EditIngredientData {
  name: string;
  amount?: number | null;
  unit?: string | null;
  group?: string | null;
}

export interface EditRecipeFormIngredientData {
  name: string;
  amount: number | null;
  unit: string | null;
  group: string | null;
  recipeIngredientId: number | null;
}
