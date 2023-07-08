<?php

namespace Database\Seeders;

use App\Models\RecipeImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Console\View\Components\Info;

class DatabaseSeeder extends Seeder {
    public function run() {
        $this->pruneImages();

        $user = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        (new Info($this->command->getOutput()))->render(
            "Admin user created: '{$user->email}' (Password: 'password')"
        );
    }

    private function pruneImages() {
        app(RecipeImage::class)->pruneImages();
    }
}
