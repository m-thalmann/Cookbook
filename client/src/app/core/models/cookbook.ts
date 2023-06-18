import { User } from './user';

export interface Cookbook {
  id: number;
  name: string;
  created_at: number;
  updated_at: number;
}

export interface CookbookWithCounts extends Cookbook {
  recipes_count: number;
}

export interface CookbookWithUserMeta extends Cookbook {
  meta: {
    is_admin: boolean;
    created_at: number;
    updated_at: number;
  };
}

export interface SimpleCookbook {
  id: number;
  name: string;
}

export interface CookbookUser extends User {
  meta: {
    is_admin: boolean;
    created_at: number;
    updated_at: number;
  };
}
