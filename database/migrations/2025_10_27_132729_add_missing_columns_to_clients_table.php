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
            $table->string('statut')->default('actif');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->date('dateNaissance')->nullable();
            $table->text('adresse')->nullable();
            $table->enum('genre', ['M', 'F'])->nullable();
            $table->string('code_auth')->nullable();
            $table->timestamp('code_expires_at')->nullable();

            // Modifier la colonne cni pour qu'elle soit nullable
            $table->string('cni')->nullable()->change();

            // Index pour les performances
            $table->index('statut');
            $table->index('email');
            $table->index('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropIndex(['email']);
            $table->dropIndex(['telephone']);
            $table->dropColumn([
                'statut',
                'nom',
                'prenom',
                'email',
                'telephone',
                'dateNaissance',
                'adresse',
                'genre',
                'code_auth',
                'code_expires_at'
            ]);
        });
    }
};
