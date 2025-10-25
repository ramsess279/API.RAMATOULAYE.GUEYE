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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('numeroCompte')->unique();
            $table->enum("type", ['epargne', 'cheque']);
            $table->string('devise')->default('XOF');
            $table->enum("statut", ['actif', 'bloque', 'ferme'])->default('actif');
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->timestamps();

            // Index pour les performances
            $table->index('numeroCompte');
            $table->index('type');
            $table->index('statut');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compte_models');
    }
};
