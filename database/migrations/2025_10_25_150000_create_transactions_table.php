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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->decimal('montant', 15, 2);
            $table->enum("type", ['depot', 'retrait', 'transfert']);
            $table->text('description')->nullable();
            $table->uuid('compte_id');
            $table->foreign('compte_id')->references('id')->on('comptes')->cascadeOnDelete();
            $table->timestamp('date_transaction')->useCurrent();
            $table->timestamps();

            // Index de base de données
            $table->index(['compte_id', 'date_transaction']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};