<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\CompteModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompteModel>
 */
class CompteModelFactory extends Factory
{
    protected $model = CompteModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Récupérer un client existant aléatoirement
        $client = \App\Models\Client::inRandomOrder()->first();

        // Si aucun client n'existe, en créer un
        if (!$client) {
            $client = \App\Models\Client::factory()->create();
        }

        return [
            "type" => fake()->randomElement(['cheque', 'epargne']),
            "statut" => fake()->randomElement(['actif', 'bloque', 'ferme']),
            "devise" => 'XOF',
            'client_id' => $client->id,
        ];
    }

    // Temporarily disable transaction generation to fix seeding issues
    // TODO: Re-enable after fixing UUID issues

    /**
     * Génère une description appropriée selon le type de transaction
     */
    private function genererDescriptionTransaction(string $type): string
    {
        return match ($type) {
            'depot' => fake()->randomElement([
                'Dépôt en espèces',
                'Virement bancaire',
                'Dépôt chèque',
                'Versement salaire'
            ]),
            'retrait' => fake()->randomElement([
                'Retrait DAB',
                'Paiement facture',
                'Retrait espèces',
                'Achat commerce'
            ]),
            'transfert' => fake()->randomElement([
                'Transfert vers compte épargne',
                'Virement SEPA',
                'Paiement fournisseur',
                'Transfert international'
            ]),
            default => 'Transaction'
        };
    }
}
