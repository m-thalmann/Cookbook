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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table
                ->foreignId('cookbook_id')
                ->nullable()
                ->constrained('cookbooks')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->boolean('is_public')->default(false);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('portions')->nullable();
            $table->integer('difficulty')->nullable();
            $table
                ->text('preparation')
                ->comment('Contains sanitized HTML')
                ->nullable();
            $table->integer('preparation_time_minutes')->nullable();
            $table->integer('resting_time_minutes')->nullable();
            $table->integer('cooking_time_minutes')->nullable();
            $table
                ->uuid('share_uuid')
                ->unique()
                ->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('recipes');
    }
};
