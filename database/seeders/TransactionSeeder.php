<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\CompteModel;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Les transactions sont générées automatiquement via les factories des comptes
        // Ce seeder est vide car toute la logique est dans CompteModelFactory
        $this->command->info('Les transactions sont générées automatiquement via les factories des comptes.');
    }
}