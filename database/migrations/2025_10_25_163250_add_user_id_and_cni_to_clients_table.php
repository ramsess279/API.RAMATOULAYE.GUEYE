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
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('cni')->unique();
            $table->timestamp('date_delivrance_cni')->nullable();
            $table->timestamp('date_expiration_cni')->nullable();
            $table->string('lieu_delivrance_cni')->nullable();

            // Index pour les performances
            $table->index('user_id');
            $table->index('cni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['cni']);
            $table->dropColumn(['user_id', 'cni', 'date_delivrance_cni', 'date_expiration_cni', 'lieu_delivrance_cni']);
        });
    }
};
