<?php

namespace App\Services;

use App\Models\CompteModel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CompteService
{
    /**
     * Récupère la liste paginée des comptes selon les critères
     *
     * @param Request $request
     * @param int|null $clientId Filtre optionnel par client (pour les clients)
     * @return LengthAwarePaginator
     */
    public function getComptesPagines(Request $request, ?int $clientId = null): LengthAwarePaginator
    {
        // Construction de la requête
        $query = CompteModel::with(['client.user']);

        // Appliquer le filtre client si fourni (pour l'autorisation)
        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        // Appliquer les filtres
        $query = $this->applyFilters($query, $request);

        // Appliquer le tri
        $query = $this->applySorting($query, $request);

        // Pagination
        $perPage = $request->get('limit', 10);
        return $query->paginate($perPage);
    }

    /**
     * Applique les filtres à la requête
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, Request $request)
    {
        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Recherche textuelle
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numeroCompte', 'like', "%{$search}%")
                  ->orWhereHas('client.user', function ($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%")
                                ->orWhere('telephone', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Applique le tri à la requête
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySorting($query, Request $request)
    {
        $sortField = $request->get('sort', 'dateCreation');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortField) {
            case 'solde':
                // Pour le tri par solde, nous utilisons created_at comme proxy
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'titulaire':
                $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                      ->join('users', 'clients.user_id', '=', 'users.id')
                      ->orderBy('users.nom', $sortOrder)
                      ->orderBy('users.prenom', $sortOrder)
                      ->select('comptes.*');
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        return $query;
    }

    /**
     * Transforme les données des comptes pour la réponse API
     *
     * @param LengthAwarePaginator $comptes
     * @return array
     */
    public function transformComptesData(LengthAwarePaginator $comptes): array
    {
        return collect($comptes->items())->map(function ($compte) {
            return [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'titulaire' => $compte->client->user->prenom . ' ' . $compte->client->user->nom,
                'type' => $compte->type,
                'solde' => $compte->getSolde(),
                'devise' => $compte->devise,
                'dateCreation' => $compte->created_at->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->statut === 'bloque' ? 'Inactivité de 30+ jours' : null,
                'metadata' => [
                    'derniereModification' => $compte->updated_at->toISOString(),
                    'version' => 1
                ]
            ];
        })->toArray();
    }
}