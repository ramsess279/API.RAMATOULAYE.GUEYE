<?php

namespace App\Services;

use App\Models\CompteModel;
use App\Models\CompteArchive;
use App\Services\ArchiveService;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CompteService
{
    private ArchiveService $archiveService;

    public function __construct(ArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    /**
     * Récupère la liste paginée des comptes selon les critères
     * Inclut maintenant les comptes archivés si demandé
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

        // Inclure les comptes supprimés (soft delete) si demandé
        if ($request->boolean('withTrashed')) {
            $query->withTrashed();
        }

        // Inclure les comptes archivés si demandé
        $includeArchived = $request->boolean('includeArchived');

        // Filtre par défaut : afficher uniquement les comptes actifs si aucun statut n'est spécifié
        // et si on n'inclut pas les archivés
        if (!$request->filled('statut') && !$includeArchived) {
            $query->where('statut', 'actif');
        }

        // Appliquer les filtres
        $query = $this->applyFilters($query, $request);

        // Appliquer le tri
        $query = $this->applySorting($query, $request);

        // Pagination
        $perPage = $request->get('limit', 10);
        $comptes = $query->paginate($perPage);

        // Si on doit inclure les archivés, les ajouter à la collection
        if ($includeArchived) {
            $comptes = $this->mergeWithArchived($comptes, $request);
        }

        return $comptes;
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
     * Supporte les comptes actifs et archivés
     *
     * @param CompteModel|CompteArchive $compte
     * @return array
     */
    public function transformCompteData($compte): array
    {
        $isArchived = $compte instanceof CompteArchive || ($compte->source ?? null) === 'archive';

        if ($isArchived) {
            // Transformation pour compte archivé
            $data = [
                'numeroCompte' => $compte->numeroCompte,
                'clientId' => null, // Pas d'ID client dans l'archive
                'cni' => $compte->client_cni,
                'titulaire' => $compte->client_prenom . ' ' . $compte->client_nom,
                'type' => $compte->type,
                'solde' => $compte->solde, // Solde figé au moment de l'archivage
                'devise' => $compte->devise,
                'dateCreation' => $compte->created_at?->toISOString(),
                'statut' => $compte->statut,
                'source' => 'archive', // Indicateur que c'est un compte archivé
                'dateArchivage' => $compte->dateArchivage?->toISOString(),
                'motifBlocage' => $compte->motifBlocage,
                'metadata' => [
                    'derniereModification' => $compte->updated_at?->toISOString(),
                    'version' => 1
                ]
            ];

            // Ajouter les informations de blocage si le compte était bloqué
            if ($compte->statut === 'bloque') {
                $data['blocage'] = [
                    'dateDebut' => $compte->dateBlocage?->toDateString(),
                    'dateFinPrevue' => $compte->dateDeblocagePrevue?->toDateString(),
                    'duree' => $compte->dureeBlocage,
                    'unite' => $compte->uniteBlocage,
                    'dureeEnJours' => $compte->dureeBlocageJours,
                    'motif' => $compte->motifBlocage
                ];
            }
        } else {
            // Transformation pour compte actif (logique existante)
            $calculSoldeService = app(CalculSoldeService::class);
            $soldeCalcule = $calculSoldeService->calculerSolde($compte);

            $data = [
                'numeroCompte' => $compte->numeroCompte,
                'clientId' => $compte->client->id,
                'cni' => $compte->client->cni,
                'titulaire' => $compte->client->user->prenom . ' ' . $compte->client->user->nom,
                'type' => $compte->type,
                'solde' => $soldeCalcule, // Retourner le solde calculé depuis les transactions
                'devise' => $compte->devise,
                'dateCreation' => $compte->created_at->toISOString(),
                'statut' => $compte->statut,
                'motifBlocage' => $compte->statut === 'bloque' ? ($compte->motifBlocage ?: 'Inactivité de 30+ jours') : null,
                'metadata' => [
                    'derniereModification' => $compte->updated_at->toISOString(),
                    'version' => 1
                ]
            ];

            // Ajouter les informations de blocage si le compte est bloqué
            if ($compte->statut === 'bloque') {
                $data['blocage'] = [
                    'dateDebut' => $compte->dateBlocage,
                    'dateFinPrevue' => $compte->dateDeblocagePrevue,
                    'duree' => $compte->dureeBlocage,
                    'unite' => $compte->uniteBlocage,
                    'dureeEnJours' => $compte->dureeBlocageJours,
                    'motif' => $compte->motifBlocage
                ];
            }
        }

        return $data;
    }

    /**
     * Transforme les données des comptes pour la réponse API
     * Supporte les comptes actifs et archivés
     *
     * @param LengthAwarePaginator $comptes
     * @return array
     */
    public function transformComptesData(LengthAwarePaginator $comptes): array
    {
        $calculSoldeService = app(CalculSoldeService::class);

        return collect($comptes->items())->map(function ($compte) use ($calculSoldeService) {
            $isArchived = $compte instanceof CompteArchive || ($compte->source ?? null) === 'archive';

            if ($isArchived) {
                // Transformation pour compte archivé
                $data = [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'clientId' => null, // Pas d'ID client dans l'archive
                    'cni' => $compte->client_cni,
                    'titulaire' => $compte->client_prenom . ' ' . $compte->client_nom,
                    'type' => $compte->type,
                    'solde' => $compte->solde, // Solde figé au moment de l'archivage
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->created_at?->toISOString(),
                    'statut' => $compte->statut,
                    'source' => 'archive', // Indicateur que c'est un compte archivé
                    'dateArchivage' => $compte->dateArchivage?->toISOString(),
                    'motifBlocage' => $compte->motifBlocage,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at?->toISOString(),
                        'version' => 1
                    ]
                ];

                // Ajouter les informations de blocage si le compte était bloqué
                if ($compte->statut === 'bloque') {
                    $data['blocage'] = [
                        'dateDebut' => $compte->dateBlocage?->toDateString(),
                        'dateFinPrevue' => $compte->dateDeblocagePrevue?->toDateString(),
                        'duree' => $compte->dureeBlocage,
                        'unite' => $compte->uniteBlocage,
                        'dureeEnJours' => $compte->dureeBlocageJours,
                        'motif' => $compte->motifBlocage
                    ];
                }
            } else {
                // Transformation pour compte actif (logique existante)
                $soldeCalcule = $calculSoldeService->calculerSolde($compte);

                $data = [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numeroCompte,
                    'clientId' => $compte->client->id,
                    'cni' => $compte->client->cni,
                    'titulaire' => $compte->client->user->prenom . ' ' . $compte->client->user->nom,
                    'type' => $compte->type,
                    'solde' => $soldeCalcule, // Retourner le solde calculé depuis les transactions
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->created_at->toISOString(),
                    'statut' => $compte->statut,
                    'motifBlocage' => $compte->statut === 'bloque' ? ($compte->motifBlocage ?: 'Inactivité de 30+ jours') : null,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at->toISOString(),
                        'version' => 1
                    ]
                ];

                // Ajouter les informations de blocage si le compte est bloqué
                if ($compte->statut === 'bloque') {
                    $data['blocage'] = [
                        'dateDebut' => $compte->dateBlocage,
                        'dateFinPrevue' => $compte->dateDeblocagePrevue,
                        'duree' => $compte->dureeBlocage,
                        'unite' => $compte->uniteBlocage,
                        'dureeEnJours' => $compte->dureeBlocageJours,
                        'motif' => $compte->motifBlocage
                    ];
                }
            }

            return $data;
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
     * Règles : seuls les comptes actifs peuvent être supprimés (passent à 'ferme')
     * Les comptes bloqués ne peuvent pas être supprimés directement
     *
     * @param string $compteId
     * @return CompteModel
     * @throws CompteNotFoundException
     */
    public function deleteCompte(string $compteId): CompteModel
    {
        // Récupérer le compte
        $compte = $this->getCompteByIdWithTrashed($compteId);

        // Vérifier que le compte peut être supprimé (seuls les comptes actifs)
        if ($compte->statut !== 'actif') {
            throw new \Exception('Seul un compte actif peut être supprimé.');
        }

        // Changer le statut à 'ferme' avant la suppression
        $compte->statut = 'ferme';
        $compte->save();

        // Soft delete
        $compte->delete();

        return $compte;
    }

    /**
     * Bloque un compte bancaire et l'archive automatiquement vers Neon
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

        // Utiliser les dates fournies par l'utilisateur
        $dateBlocageProgramme = $data['dateDebut'];
        $dateDeblocagePrevue = $data['dateFin'];

        // Calculer la durée en jours pour l'affichage
        $dateDebutCarbon = \Carbon\Carbon::parse($dateBlocageProgramme);
        $dateFinCarbon = \Carbon\Carbon::parse($dateDeblocagePrevue);
        $dureeEnJours = $dateDebutCarbon->diffInDays($dateFinCarbon);

        // Déterminer l'unité et la durée approximative
        if ($dureeEnJours <= 90) {
            $unite = 'jours';
            $duree = $dureeEnJours;
        } else {
            $unite = 'mois';
            $duree = round($dureeEnJours / 30); // Approximation
        }

        // Vérifier si la date de blocage est aujourd'hui ou dans le passé
        $aujourdHui = now()->toDateString();
        if ($dateBlocageProgramme <= $aujourdHui) {
            // Bloquer immédiatement
            $compte->statut = 'bloque';
            $compte->motifBlocage = $data['motif'];
            $compte->dateBlocage = $aujourdHui;
            $compte->dateDeblocagePrevue = $dateDeblocagePrevue;
            $compte->dureeBlocage = $duree;
            $compte->uniteBlocage = $unite;
            $compte->dureeBlocageJours = $dureeEnJours;

            // Archiver automatiquement vers Neon lors du blocage immédiat
            try {
                $this->archiveService->archiverCompteBloque($compte);
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas bloquer le blocage du compte
                \Illuminate\Support\Facades\Log::error('Erreur lors de l\'archivage automatique: ' . $e->getMessage(), [
                    'compte_id' => $compte->id,
                    'compte_numero' => $compte->numeroCompte
                ]);
            }
        } else {
            // Programmer le blocage pour plus tard
            $compte->statut = 'actif'; // Rester actif
            $compte->dateBlocageProgramme = $dateBlocageProgramme;
            $compte->dateDeblocagePrevue = $dateDeblocagePrevue;
            $compte->dureeBlocageProgramme = $duree;
            $compte->uniteBlocageProgramme = $unite;
            $compte->motifBlocageProgramme = $data['motif'];
        }

        $compte->save();

        return $compte;
    }

    /**
     * Débloque un compte bancaire et supprime son archive
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

        // Supprimer l'archive de Neon lors du déblocage
        try {
            $this->archiveService->supprimerArchive($compteId);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer le déblocage du compte
            \Illuminate\Support\Facades\Log::warning('Erreur lors de la suppression de l\'archive: ' . $e->getMessage(), [
                'compte_id' => $compte->id,
                'compte_numero' => $compte->numeroCompte
            ]);
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

    /**
     * Fusionne les comptes actifs avec les comptes archivés pour la pagination
     *
     * @param LengthAwarePaginator $comptes
     * @param Request $request
     * @return LengthAwarePaginator
     */
    private function mergeWithArchived(LengthAwarePaginator $comptes, Request $request): LengthAwarePaginator
    {
        // Récupérer les critères de recherche pour les comptes archivés
        $criteria = [];

        if ($request->filled('search')) {
            $search = $request->search;
            // Pour les comptes archivés, on cherche dans numéro, nom, prénom, email, téléphone
            $criteria['search'] = $search;
        }

        if ($request->filled('type')) {
            $criteria['type'] = $request->type;
        }

        if ($request->filled('statut')) {
            $criteria['statut'] = $request->statut;
        }

        // Récupérer les comptes archivés
        $comptesArchives = $this->archiveService->rechercherComptesArchives($criteria);

        // Convertir les comptes archivés en format similaire aux comptes actifs
        $comptesArchivesFormates = $comptesArchives->map(function ($compteArchive) {
            return (object) [
                'id' => $compteArchive->id,
                'numeroCompte' => $compteArchive->numeroCompte,
                'type' => $compteArchive->type,
                'devise' => $compteArchive->devise,
                'statut' => $compteArchive->statut,
                'solde' => $compteArchive->solde,
                'dateCreation' => $compteArchive->created_at?->toISOString(),
                'dateArchivage' => $compteArchive->dateArchivage?->toISOString(),
                'source' => 'archive', // Indicateur pour distinguer les comptes archivés
                'client' => (object) [
                    'id' => null, // Pas d'ID client dans l'archive
                    'cni' => $compteArchive->client_cni,
                    'user' => (object) [
                        'prenom' => $compteArchive->client_prenom,
                        'nom' => $compteArchive->client_nom,
                        'email' => $compteArchive->client_email,
                        'telephone' => $compteArchive->client_telephone,
                    ]
                ],
                'motifBlocage' => $compteArchive->motifBlocage,
                'dateBlocage' => $compteArchive->dateBlocage?->toDateString(),
                'dateDeblocagePrevue' => $compteArchive->dateDeblocagePrevue?->toDateString(),
                'dureeBlocage' => $compteArchive->dureeBlocage,
                'uniteBlocage' => $compteArchive->uniteBlocage,
                'dureeBlocageJours' => $compteArchive->dureeBlocageJours,
                'created_at' => $compteArchive->created_at,
                'updated_at' => $compteArchive->updated_at,
            ];
        });

        // Fusionner les collections
        $tousLesComptes = collect($comptes->items())->merge($comptesArchivesFormates);

        // Appliquer le tri sur la collection fusionnée
        $tousLesComptes = $this->applySortingToCollection($tousLesComptes, $request);

        // Créer une nouvelle pagination avec la collection fusionnée
        $perPage = $comptes->perPage();
        $currentPage = $comptes->currentPage();
        $total = $tousLesComptes->count();

        // Paginer la collection fusionnée
        $items = $tousLesComptes->forPage($currentPage, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $comptes->path(),
                'pageName' => $comptes->getPageName(),
            ]
        );
    }

    /**
     * Applique le tri à une collection de comptes (utilisé pour la fusion)
     *
     * @param Collection $comptes
     * @param Request $request
     * @return Collection
     */
    private function applySortingToCollection(Collection $comptes, Request $request): Collection
    {
        $sortField = $request->get('sort', 'dateCreation');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortField) {
            case 'solde':
                return $comptes->sortBy('solde', SORT_REGULAR, $sortOrder === 'desc');
            case 'titulaire':
                return $comptes->sortBy(function ($compte) {
                    return $compte->client->user->nom . ' ' . $compte->client->user->prenom;
                }, SORT_NATURAL, $sortOrder === 'desc');
            default:
                return $comptes->sortBy('created_at', SORT_REGULAR, $sortOrder === 'desc');
        }
    }

    /**
     * Récupère un compte spécifique par son ID (recherche hybride DB principale + archive)
     *
     * @param string $compteId
     * @param int|null $clientId Filtre optionnel par client (pour les clients)
     * @return CompteModel|CompteArchive|null
     * @throws CompteNotFoundException
     */
    public function getCompteByIdHybrid(string $compteId, ?int $clientId = null)
    {
        // D'abord chercher dans la DB principale
        try {
            $compte = $this->getCompteById($compteId, $clientId);
            return $compte;
        } catch (CompteNotFoundException $e) {
            // Si pas trouvé dans la DB principale, chercher dans l'archive
            $compteArchive = $this->archiveService->rechercherDansArchive($compteId);

            if ($compteArchive) {
                // Convertir en objet similaire pour compatibilité
                $compteArchive->source = 'archive'; // Marquer comme compte archivé
                return $compteArchive;
            }

            // Si pas trouvé nulle part, lever l'exception
            throw $e;
        }
    }
}