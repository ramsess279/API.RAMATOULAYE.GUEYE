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
        // Créer 10 comptes sans transactions pour éviter les problèmes UUID
        $comptes = CompteModel::factory()->count(10)->create();

        // Afficher les comptes créés
        $this->command->info('Comptes créés:');
        foreach ($comptes as $compte) {
            $this->command->info("Compte {$compte->numeroCompte}: {$compte->type} - {$compte->statut}");
        }
    }
}
