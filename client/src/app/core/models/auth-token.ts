export interface AuthToken {
  id: number;
  type: 'access' | 'refresh';
  tokenable_type: string;
  tokenable_id: number;
  group_id: number;
  name: string;
  abilities: string[];
  revoked_at: number;
  expires_at: number;
  created_at: number;
  updated_at: number;
  is_current: boolean;
}
