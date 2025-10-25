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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->date('dateNaissance')->nullable();
            $table->string('adresse')->nullable();
            $table->enum('genre', ['homme', 'femme', 'autre'])->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();

            // Index pour les performances
            $table->index('email');
            $table->index('telephone');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
