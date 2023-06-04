export interface BaseIngredient {
  name: string;
  amount: number | null;
  unit: string | null;
  group: string | null;
  order_index: number;
}

export interface Ingredient extends BaseIngredient {
  id: number;
  recipe_id: number;
  created_at: number;
  updated_at: number;
}

export interface SimpleIngredient {
  name: string;
  unit: string | null;
}

export interface EditIngredientData {
  name?: string;
  amount?: number | null;
  unit?: string | null;
  group?: string | null;
  order_index?: number;
}

export interface EditRecipeFormIngredientData extends BaseIngredient {
  recipeIngredientId: number | null;
}
