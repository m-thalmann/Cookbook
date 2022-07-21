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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('recipe_id')
                ->constrained('recipes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name', 40);
            $table->float('amount')->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('group', 20)->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'name', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('ingredients');
    }
};

