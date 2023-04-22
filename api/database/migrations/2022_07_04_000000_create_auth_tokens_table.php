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
        Schema::create('auth_tokens', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['refresh', 'access']);
            $table->morphs('tokenable');
            $table->integer('group_id')->nullable();
            $table->string('name');
            $table->string('token', 64);
            $table->text('abilities')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_host')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('auth_tokens');
    }
};

