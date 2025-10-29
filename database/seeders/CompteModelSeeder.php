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
        // Créer des comptes pour les clients spécifiques créés dans ClientSeeder
        $clients = \App\Models\Client::with('user')->get();

        foreach ($clients as $client) {
            // Créer 1-2 comptes par client
            $nombreComptes = rand(1, 2);

            for ($i = 0; $i < $nombreComptes; $i++) {
                CompteModel::factory()->create([
                    'client_id' => $client->id,
                ]);
            }
        }

        // Créer quelques comptes supplémentaires sans transactions pour éviter les problèmes UUID
        $comptesSupplementaires = CompteModel::factory()->count(5)->create();

        // Afficher les comptes créés
        $this->command->info('Comptes créés pour les clients:');
        $totalComptes = CompteModel::count();
        $this->command->info("Total de comptes créés: {$totalComptes}");

        // Afficher quelques exemples
        $exemples = CompteModel::with('client.user')->take(5)->get();
        foreach ($exemples as $compte) {
            $titulaire = $compte->client->user->prenom . ' ' . $compte->client->user->nom;
            $this->command->info("Compte {$compte->numeroCompte}: {$compte->type} - {$compte->statut} - Titulaire: {$titulaire}");
        }
    }
}
