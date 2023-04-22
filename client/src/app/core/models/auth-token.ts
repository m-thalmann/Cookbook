export interface AuthToken {
  id: number;
  type: 'access' | 'refresh';
  tokenable_type: string;
  tokenable_id: number;
  group_id: number | null;
  name: string;
  abilities: string[];
  ip_address: string | null;
  ip_host: string | null;
  user_agent: string | null;
  user_agent_details: {
    name: string;
    name_key: string;
    os: string;
    version: string;
  } | null;
  revoked_at: number | null;
  expires_at: number | null;
  created_at: number;
  updated_at: number;
  is_current: boolean;
}
