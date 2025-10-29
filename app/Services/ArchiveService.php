<?php

namespace App\Services;

use App\Models\CompteModel;
use App\Models\CompteArchive;
use App\Services\CalculSoldeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service responsable de l'archivage des comptes bloqués vers la base Neon
 */
class ArchiveService
{
    private CalculSoldeService $calculSoldeService;

    public function __construct(CalculSoldeService $calculSoldeService)
    {
        $this->calculSoldeService = $calculSoldeService;
    }

    /**
     * Archive un compte bloqué vers la base Neon
     *
     * @param CompteModel $compte Le compte à archiver
     * @return bool True si l'archivage a réussi
     * @throws \Exception En cas d'erreur d'archivage
     */
    public function archiverCompteBloque(CompteModel $compte): bool
    {
        try {
            // Vérifier que le compte est bien bloqué
            if ($compte->statut !== 'bloque') {
                throw new \Exception('Seul un compte bloqué peut être archivé.');
            }

            // Calculer le solde actuel
            $solde = $this->calculSoldeService->calculerSolde($compte);

            // Préparer les données pour l'archive avec gestion des valeurs nulles
            $archiveData = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'type' => $compte->type,
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                'client_nom' => $compte->client->user->nom,
                'client_prenom' => $compte->client->user->prenom,
                'client_email' => $compte->client->user->email,
                'client_telephone' => $compte->client->user->telephone,
                'client_cni' => $compte->client->cni,
                'solde' => $solde,
                'dateBlocage' => $compte->dateBlocage ?? now()->toDateString(),
                'dateDeblocagePrevue' => $compte->dateDeblocagePrevue ?? now()->addDays(30)->toDateString(),
                'motifBlocage' => $compte->motifBlocage ?? 'Blocage automatique',
                'dureeBlocage' => $compte->dureeBlocage ?? 30,
                'uniteBlocage' => $compte->uniteBlocage ?? 'jours',
                'dureeBlocageJours' => $compte->dureeBlocageJours ?? 30,
                'dateArchivage' => now(),
            ];

            // Utiliser une transaction pour garantir l'intégrité
            DB::connection('archive')->beginTransaction();

            try {
                // Créer l'archive dans Neon
                CompteArchive::create($archiveData);

                DB::connection('archive')->commit();

                Log::info("Compte {$compte->numeroCompte} archivé avec succès vers Neon", [
                    'compte_id' => $compte->id,
                    'solde' => $solde,
                    'dateArchivage' => now()->toISOString()
                ]);

                return true;

            } catch (\Exception $e) {
                DB::connection('archive')->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'archivage du compte {$compte->numeroCompte}: " . $e->getMessage(), [
                'compte_id' => $compte->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Supprime un compte de l'archive (lors du déblocage)
     *
     * @param string $compteId L'ID du compte à désarchiver
     * @return bool True si la suppression a réussi
     * @throws \Exception En cas d'erreur de suppression
     */
    public function supprimerArchive(string $compteId): bool
    {
        try {
            $archive = CompteArchive::where('id', $compteId)->first();

            if (!$archive) {
                Log::warning("Tentative de suppression d'une archive inexistante", [
                    'compte_id' => $compteId
                ]);
                return true; // Considérer comme réussi si l'archive n'existe pas
            }

            $archive->delete();

            Log::info("Archive du compte {$archive->numeroCompte} supprimée avec succès", [
                'compte_id' => $compteId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression de l'archive du compte {$compteId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Recherche un compte dans l'archive
     *
     * @param string $compteId L'ID du compte recherché
     * @return CompteArchive|null Le compte archivé ou null s'il n'existe pas
     */
    public function rechercherDansArchive(string $compteId): ?CompteArchive
    {
        try {
            return CompteArchive::where('id', $compteId)->first();
        } catch (\Exception $e) {
            Log::error("Erreur lors de la recherche dans l'archive: " . $e->getMessage(), [
                'compte_id' => $compteId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Recherche un compte archivé par numéro de compte
     *
     * @param string $numeroCompte Le numéro du compte recherché
     * @return CompteArchive|null Le compte archivé ou null s'il n'existe pas
     */
    public function rechercherParNumero(string $numeroCompte): ?CompteArchive
    {
        try {
            return CompteArchive::where('numeroCompte', $numeroCompte)->first();
        } catch (\Exception $e) {
            Log::error("Erreur lors de la recherche par numéro dans l'archive: " . $e->getMessage(), [
                'numeroCompte' => $numeroCompte,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Recherche des comptes archivés selon des critères
     *
     * @param array $criteria Critères de recherche
     * @return \Illuminate\Database\Eloquent\Collection Collection des comptes archivés
     */
    public function rechercherComptesArchives(array $criteria = []): \Illuminate\Database\Eloquent\Collection
    {
        try {
            $query = CompteArchive::query();

            // Appliquer les filtres
            if (isset($criteria['numeroCompte'])) {
                $query->numero($criteria['numeroCompte']);
            }

            if (isset($criteria['client_email'])) {
                $query->clientEmail($criteria['client_email']);
            }

            if (isset($criteria['client_telephone'])) {
                $query->clientTelephone($criteria['client_telephone']);
            }

            if (isset($criteria['type'])) {
                $query->type($criteria['type']);
            }

            if (isset($criteria['statut'])) {
                $query->statut($criteria['statut']);
            }

            // Recherche textuelle générale
            if (isset($criteria['search'])) {
                $search = $criteria['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('numeroCompte', 'like', "%{$search}%")
                      ->orWhere('client_nom', 'like', "%{$search}%")
                      ->orWhere('client_prenom', 'like', "%{$search}%")
                      ->orWhere('client_email', 'like', "%{$search}%")
                      ->orWhere('client_telephone', 'like', "%{$search}%");
                });
            }

            // Tri par défaut
            $query->orderBy('dateArchivage', 'desc');

            return $query->get();

        } catch (\Exception $e) {
            Log::error("Erreur lors de la recherche des comptes archivés: " . $e->getMessage(), [
                'criteria' => $criteria,
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }
}