<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service pour la gestion des clients
 */
class ClientService
{
    /**
     * Recherche un client par téléphone ou CNI
     *
     * @param string $search Terme de recherche (téléphone ou CNI)
     * @return Client|null
     */
    public function rechercherClient(string $search): ?Client
    {
        // Recherche par CNI d'abord (plus précis)
        $client = Client::where('cni', $search)->first();

        if ($client) {
            return $client;
        }

        // Recherche par téléphone si pas trouvé par CNI
        $client = Client::whereHas('user', function ($query) use ($search) {
            $query->where('telephone', $search);
        })->first();

        return $client;
    }

    /**
     * Transforme les données d'un client pour la réponse API
     *
     * @param Client $client
     * @return array
     */
    public function transformClientData(Client $client): array
    {
        return [
            'id' => $client->id,
            'nom' => $client->user->nom,
            'prenom' => $client->user->prenom,
            'nomComplet' => $client->user->prenom . ' ' . $client->user->nom,
            'email' => $client->user->email,
            'telephone' => $client->user->telephone,
            'cni' => $client->cni,
            'dateNaissance' => $client->user->dateNaissance?->toISOString(),
            'adresse' => $client->user->adresse,
            'genre' => $client->user->genre,
            'statut' => $client->statut,
            'dateDelivranceCni' => $client->date_delivrance_cni?->toISOString(),
            'dateExpirationCni' => $client->date_expiration_cni?->toISOString(),
            'lieuDelivranceCni' => $client->lieu_delivrance_cni,
            'cniValide' => $client->isCniValid(),
            'dateCreation' => $client->created_at->toISOString(),
            'derniereModification' => $client->updated_at->toISOString(),
            'metadata' => [
                'version' => 1
            ]
        ];
    }
}