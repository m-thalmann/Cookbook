import { Cookbook } from './cookbook';
import { BaseIngredient, EditRecipeFormIngredientData, Ingredient } from './ingredient';
import { RecipeImage } from './recipe-image';
import { SortOption } from './sort-option';
import { User } from './user';

export interface BasicRecipe {
  cookbook_id: number | null;
  is_public: boolean;
  name: string;
  description: string | null;
  category: string | null;
  portions: number | null;
  difficulty: number | null;
  preparation: string | null;
  preparation_time_minutes: number | null;
  resting_time_minutes: number | null;
  cooking_time_minutes: number | null;
}

export interface Recipe extends BasicRecipe {
  id: number;
  user_id: number;
  deleted_at: number | null;
  created_at: number;
  updated_at: number;
  user: User;
  user_can_edit?: boolean;
}

export interface ListRecipe extends Recipe {
  thumbnail: RecipeImage | null;
  cookbook: Cookbook | null;
}

export interface DetailedRecipe extends Recipe {
  share_uuid: string | null;
  ingredients: {
    group: string | null;
    items: Ingredient[];
  }[];
  images: RecipeImage[];
  cookbook?: Cookbook | null;
}

export interface CreateRecipeData extends BasicRecipe {
  ingredients: BaseIngredient[];
}

export interface EditRecipeData {
  user_id?: number;
  name?: string;
  is_public?: boolean;
  is_shared?: boolean;
  description?: string | null;
  category?: string | null;
  portions?: number | null;
  difficulty?: number | null;
  preparation?: string | null;
  preparation_time_minutes?: number | null;
  resting_time_minutes?: number | null;
  cooking_time_minutes?: number | null;
  cookbook_id?: number | null;
}

export interface EditRecipeFormData extends BasicRecipe {
  ingredients: EditRecipeFormIngredientData[];
}

export interface RecipeFilters {
  all?: boolean;
  search?: string;
  category?: string;
  sort?: SortOption[];
}
