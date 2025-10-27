<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Service responsable de la vérification et création de clients
 * Suit le principe de responsabilité unique (SRP)
 */
class ClientCreationService
{
    private bool $clientNewlyCreated = false;

    /**
     * Vérifie si un client existe ou le crée s'il n'existe pas
     *
     * @param array $data Données du client
     * @return Client Le client trouvé ou créé
     */
    public function getOrCreateClient(array $data): Client
    {
        // Recherche du client par téléphone (identifiant unique)
        $client = Client::whereHas('user', function ($query) use ($data) {
            $query->where('telephone', $data['client']['telephone']);
        })->first();

        if ($client) {
            $this->clientNewlyCreated = false;
            return $client;
        }

        // Créer l'utilisateur de base
        $user = $this->createUser($data['client']);

        // Créer le client
        $client = $this->createClient($user, $data['client']);

        $this->clientNewlyCreated = true;
        return $client;
    }

    /**
     * Crée un nouvel utilisateur
     *
     * @param array $data
     * @return User
     */
    private function createUser(array $data): User
    {
        // Extraire nom et prénom du titulaire
        $nomPrenom = $this->extractNomPrenom($data['titulaire']);

        return User::create([
            'nom' => $nomPrenom['nom'],
            'prenom' => $nomPrenom['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'dateNaissance' => $data['dateNaissance'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'genre' => $data['genre'] ?? null,
            'role' => 'client',
            // Le mot de passe sera défini plus tard par le service de génération de credentials
            'password' => Hash::make('temporary_password_' . time()), // Mot de passe temporaire
        ]);
    }

    /**
     * Crée un nouveau client
     *
     * @param User $user
     * @param array $data
     * @return Client
     */
    private function createClient(User $user, array $data): Client
    {
        return Client::create([
            'user_id' => $user->id,
            'statut' => 'actif',
            'cni' => $data['cni'] ?? null,
            'date_delivrance_cni' => $data['date_delivrance_cni'] ?? null,
            'date_expiration_cni' => $data['date_expiration_cni'] ?? null,
            'lieu_delivrance_cni' => $data['lieu_delivrance_cni'] ?? null,
        ]);
    }

    /**
     * Indique si le client a été créé lors de cette opération
     *
     * @return bool
     */
    public function isClientNewlyCreated(): bool
    {
        return $this->clientNewlyCreated;
    }

    /**
     * Extrait le nom et prénom du titulaire
     *
     * @param string $titulaire
     * @return array
     */
    private function extractNomPrenom(string $titulaire): array
    {
        $parts = explode(' ', trim($titulaire));
        $prenom = array_shift($parts);
        $nom = implode(' ', $parts);

        return [
            'nom' => $nom ?: $prenom, // Si pas de nom, utiliser le prénom comme nom
            'prenom' => $prenom
        ];
    }
}