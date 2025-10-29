<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur
        $adminUser = User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@banque.com',
            'password' => Hash::make('admin123'),
            'telephone' => '+221771234567',
            'dateNaissance' => '1980-01-01',
            'adresse' => 'Dakar, Sénégal',
            'genre' => 'M',
            'role' => 'admin'
        ]);

        // Créer l'entrée admin correspondante
        Admin::create([
            'user_id' => $adminUser->id,
            'permissions' => json_encode([
                'create_accounts' => true,
                'update_accounts' => true,
                'delete_accounts' => true,
                'block_accounts' => true,
                'unblock_accounts' => true,
                'view_all_accounts' => true,
                'view_reports' => true
            ])
        ]);

        // Créer des clients de test
        $clientsData = [
            [
                'nom' => 'Diallo',
                'prenom' => 'Amadou',
                'email' => 'amadou.diallo@email.com',
                'password' => Hash::make('client123'),
                'telephone' => '+221701234568',
                'dateNaissance' => '1990-05-15',
                'adresse' => 'Dakar, Sénégal',
                'genre' => 'homme',
                'cni' => '1234567890123'
            ],
            [
                'nom' => 'Sarr',
                'prenom' => 'Fatou',
                'email' => 'fatou.sarr@email.com',
                'password' => Hash::make('client123'),
                'telephone' => '+221771234569',
                'dateNaissance' => '1985-08-20',
                'adresse' => 'Saint-Louis, Sénégal',
                'genre' => 'femme',
                'cni' => '9876543210987'
            ],
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Moussa',
                'email' => 'moussa.ndiaye@email.com',
                'password' => Hash::make('client123'),
                'telephone' => '+221781234570',
                'dateNaissance' => '1992-12-10',
                'adresse' => 'Thiès, Sénégal',
                'genre' => 'homme',
                'cni' => '4567890123456'
            ],
            [
                'nom' => 'Ba',
                'prenom' => 'Aminata',
                'email' => 'aminata.ba@email.com',
                'password' => Hash::make('client123'),
                'telephone' => '+221761234571',
                'dateNaissance' => '1988-03-25',
                'adresse' => 'Kaolack, Sénégal',
                'genre' => 'femme',
                'cni' => '7890123456789'
            ],
            [
                'nom' => 'Gueye',
                'prenom' => 'Ibrahima',
                'email' => 'ibrahima.gueye@email.com',
                'password' => Hash::make('client123'),
                'telephone' => '+221751234572',
                'dateNaissance' => '1995-07-30',
                'adresse' => 'Ziguinchor, Sénégal',
                'genre' => 'M',
                'cni' => '3210987654321'
            ]
        ];

        foreach ($clientsData as $clientData) {
            $user = User::create([
                'nom' => $clientData['nom'],
                'prenom' => $clientData['prenom'],
                'email' => $clientData['email'],
                'password' => $clientData['password'],
                'telephone' => $clientData['telephone'],
                'dateNaissance' => $clientData['dateNaissance'],
                'adresse' => $clientData['adresse'],
                'genre' => $clientData['genre'],
                'role' => 'client'
            ]);

            // Créer l'entrée client correspondante
            Client::create([
                'user_id' => $user->id,
                'cni' => $clientData['cni'],
                'solde_total' => 0
            ]);
        }

        $this->command->info('Utilisateurs créés avec succès :');
        $this->command->info('Admin: admin@banque.com / admin123');
        $this->command->info('Clients: 5 clients créés avec mot de passe "client123"');
    }
}
