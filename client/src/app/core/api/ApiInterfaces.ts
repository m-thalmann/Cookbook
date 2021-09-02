export interface User {
  id: number;
  email: string;
  name: string;
}

export interface AuthUser extends User {
  isAdmin: boolean;
}

export interface UserFull extends AuthUser {
  emailVerified: boolean;
  lastUpdated: number;
}

export interface Ingredient {
  id: number;
  recipeId: number;
  name: string;
  unit: string | null;
  amount: number | null;
}

export interface Recipe {
  id: number;
  public: boolean;
  name: string;
  description: string | null;
  category: string | null;
  portions: number | null;
  difficulty: number | null;
  preparation: string | null;
  preparationTime: number | null;
  restTime: number | null;
  cookTime: number | null;
  publishDate: number;
  user: User;
  imagesCount: number;
}

export interface RecipeFull extends Recipe {
  ingredients: Ingredient[];
}

export interface RecipeImage {
  id: number;
  recipeId: number;
  mimeType: string;
}

export interface NewIngredient {
  name: string;
  unit: string | null;
  amount: string | null;
}

export interface NewRecipe {
  public: boolean;
  name: string;
  description: string | null;
  category: string | null;
  portions: number | null;
  difficulty: number | null;
  preparation: string | null;
  preparationTime: number | null;
  restTime: number | null;
  cookTime: number | null;
  ingredients: NewIngredient[];
}

export interface EditIngredient {
  name?: string;
  unit?: string | null;
  amount?: string | null;
}

export interface EditRecipe {
  public?: boolean;
  name?: string;
  description?: string | null;
  category?: string | null;
  portions?: number | null;
  difficulty?: number | null;
  preparation?: string | null;
  preparationTime?: number | null;
  restTime?: number | null;
  cookTime?: number | null;
}

export interface ListIngredient {
  name: string;
  unit: string | null;
}

export interface CategoryInformation {
  name: string;
  thumbnailRecipeId: number | null;
}

export interface ServerInformation {
  users: {
    unverified: number;
    admins: number;
    total: number;
  };
  recipes: {
    private: number;
    total: number;
  };
  imagesSize: number;
}

export interface ServerConfig {
  root_url: string;
  production: boolean;
  database: {
    host: string;
    user: string;
    database: string;
    charset: string;
  };
  image_store: string;
  token: {
    ttl: number;
  };
  password: {
    reset_ttl: number;
  };
  registration_enabled: boolean;
  email_verification: {
    enabled: boolean;
    ttl: number;
  };
  hcaptcha: {
    enabled: boolean;
  };
  mail: {
    smtp: {
      host: string;
      port: number;
      encrypted: boolean;
      username: string;
    };
    from: {
      mail: string;
      name: string;
    };
  };
}

export interface Pagination<T> {
  page: number;
  items_per_page: number;
  total_items: number;
  total_pages: number;
  items: T[];
}

export interface ApiOptions {
  page?: number;
  itemsPerPage?: number | null;
  sort?: string | null;
  sortDirection?: 'asc' | 'desc' | null;
}

interface SortDirection {
  [key: string]: 'asc' | 'desc';
}

export const RecipeSortDirection: SortDirection = {
  publishDate: 'desc',
  name: 'asc',
  category: 'asc',
  public: 'desc',
  difficulty: 'asc',
};
