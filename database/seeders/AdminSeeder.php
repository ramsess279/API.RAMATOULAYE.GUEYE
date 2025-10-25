<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un super admin
        $superAdminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@banque.sn',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'statut' => 'actif',
            'telephone' => '+221 77 000 00 00',
            'date_naissance' => '1980-01-01',
            'adresse' => 'Siège Social, Dakar',
        ]);

        Admin::create([
            'user_id' => $superAdminUser->id,
            'numero_employe' => 'ADM0001',
            'niveau_acces' => 'super_admin',
            'permissions' => ['*'],
            'derniere_connexion' => now(),
        ]);

        // Créer des admins supplémentaires
        $adminUsers = [
            [
                'name' => 'Directeur Général',
                'email' => 'dg@banque.sn',
                'numero_employe' => 'ADM0002',
                'niveau_acces' => 'admin',
            ],
            [
                'name' => 'Chef Comptable',
                'email' => 'comptable@banque.sn',
                'numero_employe' => 'ADM0003',
                'niveau_acces' => 'admin',
            ],
            [
                'name' => 'Responsable Clientèle',
                'email' => 'clientele@banque.sn',
                'numero_employe' => 'ADM0004',
                'niveau_acces' => 'moderateur',
            ],
        ];

        foreach ($adminUsers as $adminData) {
            $user = User::create([
                'name' => $adminData['name'],
                'email' => $adminData['email'],
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'statut' => 'actif',
                'telephone' => '+221 77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'date_naissance' => now()->subYears(rand(25, 50))->format('Y-m-d'),
                'adresse' => 'Agence Centrale, Dakar',
            ]);

            Admin::create([
                'user_id' => $user->id,
                'numero_employe' => $adminData['numero_employe'],
                'niveau_acces' => $adminData['niveau_acces'],
                'permissions' => $adminData['niveau_acces'] === 'admin'
                    ? ['gestion_clients', 'gestion_comptes', 'gestion_transactions', 'consultation_logs']
                    : ['gestion_clients', 'consultation_logs'],
                'derniere_connexion' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null,
            ]);
        }

        // Créer quelques admins génériques
        Admin::factory(5)->create();
    }
}
