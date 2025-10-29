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
        Schema::table('comptes', function (Blueprint $table) {
            $table->date('dateBlocageProgramme')->nullable();
            $table->integer('dureeBlocageProgramme')->nullable();
            $table->string('uniteBlocageProgramme')->nullable();
            $table->string('motifBlocageProgramme')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['dateBlocageProgramme', 'dureeBlocageProgramme', 'uniteBlocageProgramme', 'motifBlocageProgramme']);
        });
    }
};
