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
        Schema::connection('archive')->create('comptes_archive', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('numeroCompte')->unique();
            $table->enum("type", ['epargne', 'cheque']);
            $table->string('devise')->default('XOF');
            $table->enum("statut", ['actif', 'bloque', 'ferme'])->default('bloque');
            $table->string('client_nom');
            $table->string('client_prenom');
            $table->string('client_email');
            $table->string('client_telephone');
            $table->string('client_cni')->nullable();
            $table->decimal('solde', 15, 2)->default(0);
            $table->date('dateBlocage');
            $table->date('dateDeblocagePrevue');
            $table->string('motifBlocage');
            $table->integer('dureeBlocage');
            $table->string('uniteBlocage');
            $table->integer('dureeBlocageJours');
            $table->timestamp('dateArchivage');
            $table->timestamps();

            // Index pour les performances
            $table->index('numeroCompte');
            $table->index('type');
            $table->index('statut');
            $table->index('client_email');
            $table->index('dateArchivage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_archive');
    }
};
