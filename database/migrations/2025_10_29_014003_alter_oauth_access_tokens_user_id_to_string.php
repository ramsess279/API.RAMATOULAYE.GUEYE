<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->string('user_id', 36)->change(); // UUID length
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->string('user_id', 36)->change();
        });

        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->string('access_token_id', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });
    }
};
