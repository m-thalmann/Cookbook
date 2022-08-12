<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('recipe_collection_user', function (Blueprint $table) {
            $table
                ->foreignId('recipe_collection_id')
                ->constrained('recipe_collections')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->primary(['recipe_collection_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('recipe_collection_user');
    }
};

