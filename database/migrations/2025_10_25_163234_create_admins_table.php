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
        Schema::create('admins', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('numero_employe')->unique();
            $table->enum('niveau_acces', ['super_admin', 'admin', 'moderateur'])->default('admin');
            $table->json('permissions')->nullable(); // Permissions spécifiques
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index('user_id');
            $table->index('numero_employe');
            $table->index('niveau_acces');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
