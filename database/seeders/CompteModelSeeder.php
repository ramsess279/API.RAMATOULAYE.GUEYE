<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompteModel;

class CompteModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer 10 comptes avec leurs transactions automatiquement via la factory
        $comptes = CompteModel::factory()->count(10)->create();

        // Afficher les soldes calculés pour vérification
        $comptes = CompteModel::all();
        $this->command->info('Comptes créés avec leurs transactions:');
        foreach ($comptes as $compte) {
            $solde = $compte->getSolde();
            $nombreTransactions = $compte->transactions()->count();
            $this->command->info("Compte {$compte->numeroCompte}: {$nombreTransactions} transactions, Solde: {$solde} {$compte->devise}");
        }
    }
}
