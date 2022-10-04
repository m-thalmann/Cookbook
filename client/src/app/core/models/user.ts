export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  language_code: string | null;
}

export interface DetailedUser extends User {
  email_verified_at: number | null;
  is_admin: boolean;
  created_at: number;
  updated_at: number;
}
