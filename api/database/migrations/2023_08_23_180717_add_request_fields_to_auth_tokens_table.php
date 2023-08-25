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
    public function up(): void {
        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_host')->nullable();
            $table->string('user_agent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->removeColumn('ip_address');
            $table->removeColumn('ip_host');
            $table->removeColumn('user_agent');
        });
    }
};

