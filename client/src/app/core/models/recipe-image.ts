export interface RecipeImage {
  id: number;
  recipe_id: number;
  created_at: number;
  updated_at: number;
  url: string;
}

export const PLACEHOLDER_RECIPE_IMAGE_URL = 'assets/images/placeholder.jpeg';
