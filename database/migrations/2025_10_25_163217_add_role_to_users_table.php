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
        // Cette migration n'est plus nécessaire car les champs sont maintenant dans la migration principale des users
        // On la garde vide pour éviter les erreurs de rollback
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire car les champs sont maintenant dans la migration principale
    }
};
