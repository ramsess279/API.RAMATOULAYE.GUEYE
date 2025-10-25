<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs clients et leurs profils clients
        $clientUsers = [
            [
                'name' => 'Amadou Diop',
                'email' => 'amadou.diop@example.com',
                'telephone' => '+221 77 123 45 67',
                'date_naissance' => '1985-03-15',
                'adresse' => '123 Rue de la Paix, Dakar',
                'client_data' => [
                    'nom' => 'Diop',
                    'prenom' => 'Amadou',
                    'genre' => 'homme',
                    'cni' => '1234567890123',
                    'date_delivrance_cni' => '2020-01-15',
                    'date_expiration_cni' => '2030-01-15',
                    'lieu_delivrance_cni' => 'Dakar, Sénégal',
                ]
            ],
            [
                'name' => 'Fatou Sarr',
                'email' => 'fatou.sarr@example.com',
                'telephone' => '+221 78 987 65 43',
                'date_naissance' => '1990-07-22',
                'adresse' => '456 Avenue Léopold Sédar Senghor, Dakar',
                'client_data' => [
                    'nom' => 'Sarr',
                    'prenom' => 'Fatou',
                    'genre' => 'femme',
                    'cni' => '9876543210987',
                    'date_delivrance_cni' => '2019-06-10',
                    'date_expiration_cni' => '2029-06-10',
                    'lieu_delivrance_cni' => 'Dakar, Sénégal',
                ]
            ],
            [
                'name' => 'Moussa Ndiaye',
                'email' => 'moussa.ndiaye@example.com',
                'telephone' => '+221 76 555 44 33',
                'date_naissance' => '1978-11-08',
                'adresse' => '789 Boulevard de la République, Dakar',
                'client_data' => [
                    'nom' => 'Ndiaye',
                    'prenom' => 'Moussa',
                    'genre' => 'homme',
                    'cni' => '5555666677778',
                    'date_delivrance_cni' => '2021-03-20',
                    'date_expiration_cni' => '2031-03-20',
                    'lieu_delivrance_cni' => 'Saint-Louis, Sénégal',
                ]
            ],
        ];

        foreach ($clientUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password123'),
                'role' => 'client',
                'statut' => 'actif',
                'telephone' => $userData['telephone'],
                'date_naissance' => $userData['date_naissance'],
                'adresse' => $userData['adresse'],
            ]);

            Client::create(array_merge($userData['client_data'], [
                'user_id' => $user->id,
                'email' => $userData['email'],
                'telephone' => $userData['telephone'],
                'dateNaissance' => $userData['date_naissance'],
                'adresse' => $userData['adresse'],
                'statut' => 'actif',
            ]));
        }

        // Créer 10 clients supplémentaires avec factory
        Client::factory(10)->create();
    }
}
