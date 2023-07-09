<?php

namespace Database\Seeders;

use App\Models\RecipeImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    const ADMIN_PASSWORD = 'password';

    public function run() {
        $this->pruneImages();

        $user = User::make([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'language_code' => null,
        ]);

        $user->is_admin = true;
        $user->password = Hash::make(self::ADMIN_PASSWORD);

        $user->save();

        (new Info($this->command->getOutput()))->render(
            "Admin user created: '{$user->email}' (Password: '" .
                self::ADMIN_PASSWORD .
                "')"
        );
    }

    private function pruneImages() {
        app(RecipeImage::class)->pruneImages();
    }
}
