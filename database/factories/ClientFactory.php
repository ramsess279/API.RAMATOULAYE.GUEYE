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
        $dateDelivrance = $this->faker->dateTimeBetween('-5 years', '-1 year');
        $dateExpiration = $this->faker->dateTimeBetween($dateDelivrance, '+10 years');

        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'dateNaissance' => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'adresse' => $this->faker->address(),
            'genre' => $this->faker->randomElement(['homme', 'femme', 'autre']),
            'statut' => 'actif',
            'cni' => $this->faker->unique()->regexify('[0-9]{13}'),
            'date_delivrance_cni' => $dateDelivrance,
            'date_expiration_cni' => $dateExpiration,
            'lieu_delivrance_cni' => $this->faker->city() . ', Sénégal',
        ];
    }
}
