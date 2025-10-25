<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\CompteModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'montant' => $this->faker->randomFloat(2, 100, 10000),
            'type' => $this->faker->randomElement(['depot', 'retrait', 'transfert']),
            'description' => $this->faker->sentence(),
            'compte_id' => CompteModel::factory(),
            'date_transaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}