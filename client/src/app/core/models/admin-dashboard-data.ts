export interface AdminDashboardData {
  api: {
    version: number;
    environment: 'local' | 'production' | 'demo';
  };
  users: {
    admin_amount: number;
    total_amount: number;
  };
  recipes: {
    total_amount: number;
    public_amount: number;
    private_amount: number;
  };
  cookbooks: {
    total_amount: number;
  };
  recipe_images: {
    total_amount: number;
    storage_size: number;
  };
}
