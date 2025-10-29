<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Créer les utilisateurs (admin et clients) en premier
        $this->call([
            UserSeeder::class,
        ]);

        // Puis créer les autres données
        $this->call([
            CompteModelSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
 