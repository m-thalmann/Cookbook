export interface User {
  id: number;
  name: string;
  email: string;
  language_code: string | null;
}

export interface DetailedUser extends User {
  email_verified_at: number | null;
  is_admin: boolean;
  created_at: number;
  updated_at: number;
}

export interface CreateUserData {
  name: string;
  email: string;
  password: string;
  language_code?: string | null;
  is_admin?: boolean;
  is_verified?: boolean;
  send_verification_email?: boolean;
}

export interface EditUserData {
  name?: string;
  email?: string;
  password?: string;
  language_code?: string | null;
  is_admin?: boolean;
  is_verified?: boolean;
  do_logout?: boolean;
  current_password?: string;
}
