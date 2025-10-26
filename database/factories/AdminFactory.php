<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
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
            'role' => 'admin',
        ]);

        return [
            'user_id' => $user->id,
            'numero_employe' => 'ADM' . $this->faker->unique()->numberBetween(1000, 9999),
            'niveau_acces' => $this->faker->randomElement(['admin', 'moderateur']),
            'permissions' => $this->faker->randomElements([
                'gestion_clients',
                'gestion_comptes',
                'gestion_transactions',
                'gestion_admins',
                'consultation_logs'
            ], $this->faker->numberBetween(1, 5)),
            'derniere_connexion' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the admin is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'niveau_acces' => 'super_admin',
            'permissions' => ['*'],
        ]);
    }
}
