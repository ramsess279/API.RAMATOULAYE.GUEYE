<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Créer d'abord l'utilisateur
        $user = \App\Models\User::factory()->create([
            'role' => 'client',
        ]);

        $dateDelivrance = $this->faker->dateTimeBetween('-5 years', '-1 year');
        $dateExpiration = $this->faker->dateTimeBetween($dateDelivrance, '+10 years');

        return [
            'user_id' => $user->id,
            'cni' => $this->faker->unique()->regexify('[0-9]{13}'),
            'date_delivrance_cni' => $dateDelivrance,
            'date_expiration_cni' => $dateExpiration,
            'lieu_delivrance_cni' => $this->faker->city() . ', Sénégal',
        ];
    }
}
