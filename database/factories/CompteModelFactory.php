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
        return [
            'id' => (string) Str::uuid(),
            "type" => fake()->randomElement(['cheque', 'epargne']),
            "statut" => fake()->randomElement(['actif', 'bloque', 'ferme']),
            "devise" => 'XOF',
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (CompteModel $compte) {
            // Générer automatiquement des transactions fictives pour chaque compte créé
            $nombreTransactions = rand(3, 8);

            for ($i = 0; $i < $nombreTransactions; $i++) {
                $type = fake()->randomElement(['depot', 'retrait', 'transfert']);
                $montant = fake()->randomFloat(2, 100, 5000);

                // Pour éviter un solde négatif au départ, favoriser les dépôts
                if ($i < 2) {
                    $type = 'depot';
                    $montant = fake()->randomFloat(2, 500, 10000);
                }

                // Créer directement avec les données pour éviter les problèmes de factory
                DB::table('transactions')->insert([
                    'id' => (string) Str::uuid(),
                    'compte_id' => (string) $compte->id,
                    'montant' => $montant,
                    'type' => $type,
                    'description' => $this->genererDescriptionTransaction($type),
                    'date_transaction' => fake()->dateTimeBetween('-6 months', 'now'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

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
