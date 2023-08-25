export interface AuthToken {
  id: number;
  type: 'access' | 'refresh';
  authenticatable_type: string;
  authenticatable_id: number;
  group_id: number | null;
  name: string;
  abilities: string[];
  ip_address: string | null;
  ip_host: string | null;
  user_agent: string | null;
  user_agent_details: {
    browser: string;
    os: string;
    is_desktop: boolean;
    is_mobile: boolean;
  } | null;
  revoked_at: number | null;
  expires_at: number | null;
  created_at: number;
  updated_at: number;
  is_current: boolean;
}
