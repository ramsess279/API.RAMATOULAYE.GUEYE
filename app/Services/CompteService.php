<?php

namespace App\Services;

use App\Models\CompteModel;
use App\Exceptions\CompteNotFoundException;
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
     * Récupère un compte spécifique par son ID
     *
     * @param string $compteId
     * @param int|null $clientId Filtre optionnel par client (pour les clients)
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function getCompteById(string $compteId, ?int $clientId = null): CompteModel
    {
        $query = CompteModel::with(['client.user']);

        // Appliquer le filtre client si fourni (pour l'autorisation)
        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $compte = $query->find($compteId);

        if (!$compte) {
            throw new CompteNotFoundException($compteId);
        }

        return $compte;
    }

    /**
     * Récupère un compte spécifique par son ID (même bloqué ou supprimé)
     *
     * @param string $compteId
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function getCompteByIdWithTrashed(string $compteId): CompteModel
    {
        $compte = CompteModel::with(['client.user'])->withoutGlobalScope('nonSupprimes')->find($compteId);

        if (!$compte) {
            throw new CompteNotFoundException($compteId);
        }

        return $compte;
    }

    /**
     * Transforme les données d'un compte unique pour la réponse API
     *
     * @param CompteModel $compte
     * @return array
     */
    public function transformCompteData(CompteModel $compte): array
    {
        return [
            'numeroCompte' => $compte->numeroCompte,
            'clientId' => $compte->client->id,
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
                'clientId' => $compte->client->id,
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

    /**
     * Met à jour les informations d'un compte bancaire
     *
     * @param string $compteId
     * @param array $data
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function updateCompte(string $numeroCompte, array $data): CompteModel
    {
        // Récupérer le compte par numéro
        $compte = CompteModel::with(['client.user'])->where('numeroCompte', $numeroCompte)->first();

        if (!$compte) {
            throw new CompteNotFoundException($numeroCompte);
        }

        // Mettre à jour le titulaire si fourni
        if (isset($data['titulaire'])) {
            // Séparer prénom et nom
            $noms = explode(' ', $data['titulaire'], 2);
            $compte->client->user->prenom = $noms[0] ?? '';
            $compte->client->user->nom = $noms[1] ?? '';
            $compte->client->user->save();
        }

        // Mettre à jour les informations client si fournies
        if (isset($data['informationsClient'])) {
            $clientData = $data['informationsClient'];

            if (isset($clientData['telephone'])) {
                $compte->client->user->telephone = $clientData['telephone'];
            }

            if (isset($clientData['email'])) {
                $compte->client->user->email = $clientData['email'];
            }

            if (isset($clientData['password'])) {
                $compte->client->user->password = bcrypt($clientData['password']);
            }

            if (isset($clientData['cni'])) {
                $compte->client->cni = $clientData['cni'];
            }

            $compte->client->user->save();
            $compte->client->save();
        }

        // Sauvegarder le compte pour mettre à jour updated_at
        $compte->touch();

        return $compte;
    }

    /**
     * Supprime un compte bancaire (soft delete)
     *
     * @param string $compteId
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function deleteCompte(string $compteId): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Changer le statut à 'ferme' avant la suppression
        $compte->statut = 'ferme';
        $compte->save();

        // Soft delete
        $compte->delete();

        return $compte;
    }

    /**
     * Bloque un compte bancaire
     *
     * @param string $compteId
     * @param array $data
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function bloquerCompte(string $compteId, array $data): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Vérifier que le compte est actif
        if ($compte->statut !== 'actif') {
            throw new \Exception('Seul un compte actif peut être bloqué.');
        }

        // Vérifier que c'est un compte épargne
        if ($compte->type !== 'epargne') {
            throw new \Exception('Seul un compte épargne peut être bloqué.');
        }

        // Calculer la date de déblocage prévue
        $dateBlocage = now();
        $duree = $data['duree'];
        $unite = $data['unite'];

        if ($unite === 'jours') {
            $dateDeblocagePrevue = $dateBlocage->copy()->addDays($duree);
        } elseif ($unite === 'mois') {
            $dateDeblocagePrevue = $dateBlocage->copy()->addMonths($duree);
        } else {
            throw new \Exception('Unité de temps invalide.');
        }

        // Mettre à jour le compte
        $compte->statut = 'bloque';
        $compte->motifBlocage = $data['motif'];
        $compte->dateBlocage = $dateBlocage;
        $compte->dateDeblocagePrevue = $dateDeblocagePrevue;
        $compte->save();

        return $compte;
    }

    /**
     * Débloque un compte bancaire
     *
     * @param string $compteId
     * @param array $data
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function debloquerCompte(string $compteId, array $data): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Vérifier que le compte est bloqué
        if ($compte->statut !== 'bloque') {
            throw new \Exception('Seul un compte bloqué peut être débloqué.');
        }

        // Mettre à jour le compte
        $compte->statut = 'actif';
        $compte->motifBlocage = null;
        $compte->dateBlocage = null;
        $compte->dateDeblocagePrevue = null;
        $compte->save();

        return $compte;
    }

    /**
     * Archive manuellement un compte bancaire
     *
     * @param string $compteId
     * @param array $data
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function archiverCompte(string $compteId, array $data): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Vérifier que le compte n'est pas déjà archivé
        if ($compte->archive) {
            throw new \Exception('Ce compte est déjà archivé.');
        }

        // Archiver le compte
        $compte->archive = true;
        $compte->dateArchivage = now();
        $compte->save();

        // Archiver toutes les transactions du compte
        \App\Models\Transaction::where('compte_id', $compte->id)
            ->update(['archive' => true, 'dateArchivage' => now()]);

        return $compte;
    }

    /**
     * Désarchive manuellement un compte bancaire
     *
     * @param string $compteId
     * @param array $data
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function desarchiverCompte(string $compteId, array $data): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Vérifier que le compte est archivé
        if (!$compte->archive) {
            throw new \Exception('Ce compte n\'est pas archivé.');
        }

        // Désarchiver le compte
        $compte->archive = false;
        $compte->dateArchivage = null;
        $compte->save();

        // Désarchiver toutes les transactions du compte
        \App\Models\Transaction::where('compte_id', $compte->id)
            ->update(['archive' => false, 'dateArchivage' => null]);

        return $compte;
    }
}