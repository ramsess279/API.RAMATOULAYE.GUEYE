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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'client'])->default('client');
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->string('telephone')->nullable();
            $table->date('date_naissance')->nullable();
            $table->text('adresse')->nullable();

            // Index pour les performances
            $table->index('role');
            $table->index('statut');
            $table->index('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['statut']);
            $table->dropIndex(['telephone']);
            $table->dropColumn(['role', 'statut', 'telephone', 'date_naissance', 'adresse']);
        });
    }
};
