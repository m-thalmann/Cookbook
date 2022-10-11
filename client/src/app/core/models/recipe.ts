import { Cookbook } from './cookbook';
import { Ingredient } from './ingredient';
import { RecipeImage } from './recipe-image';
import { User } from './user';

export interface Recipe {
  id: number;
  user_id: number;
  cookbook_id: number | null;
  is_public: boolean;
  language_code: string;
  name: string;
  description: string | null;
  category: string | null;
  portions: number | null;
  difficulty: number | null;
  preparation: string | null;
  preparation_time_minutes: number | null;
  resting_time_minutes: number | null;
  cooking_time_minutes: number | null;
  deleted_at: number | null;
  created_at: number;
  updated_at: number;
  user: User;
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
