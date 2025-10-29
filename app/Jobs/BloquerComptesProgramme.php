<?php

namespace App\Jobs;

use App\Models\CompteModel;
use App\Services\ArchiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BloquerComptesProgramme implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ArchiveService $archiveService;

    /**
     * Create a new job instance.
     */
    public function __construct(ArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Récupérer la date d'aujourd'hui (sans heure)
        $aujourdHui = now()->toDateString();

        // Trouver tous les comptes qui doivent être bloqués aujourd'hui
        $comptesABloquer = CompteModel::where('statut', 'actif')
            ->where('type', 'epargne')
            ->where('dateBlocageProgramme', $aujourdHui)
            ->get();

        $comptesBloques = 0;

        foreach ($comptesABloquer as $compte) {
            try {
                // Bloquer le compte
                $compte->statut = 'bloque';
                $compte->motifBlocage = $compte->motifBlocageProgramme ?: 'Blocage automatique programmé';
                $compte->dateBlocage = $aujourdHui;

                // Calculer la date de déblocage prévue si elle n'est pas définie
                if (!$compte->dateDeblocagePrevue && $compte->dureeBlocageProgramme && $compte->uniteBlocageProgramme) {
                    if ($compte->uniteBlocageProgramme === 'jours') {
                        $compte->dateDeblocagePrevue = now()->addDays($compte->dureeBlocageProgramme)->toDateString();
                    } elseif ($compte->uniteBlocageProgramme === 'mois') {
                        $compte->dateDeblocagePrevue = now()->addMonths($compte->dureeBlocageProgramme)->toDateString();
                    }
                }

                $compte->save();

                // Archiver automatiquement vers Neon lors du blocage programmé
                try {
                    $this->archiveService->archiverCompteBloque($compte);
                    Log::info("Compte archivé automatiquement vers Neon : {$compte->numeroCompte}");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'archivage automatique du compte {$compte->numeroCompte} : " . $e->getMessage());
                    // Ne pas bloquer le blocage du compte si l'archivage échoue
                }

                $comptesBloques++;
                Log::info("Compte bloqué automatiquement : {$compte->numeroCompte}");

            } catch (\Exception $e) {
                Log::error("Erreur lors du blocage automatique du compte {$compte->numeroCompte} : " . $e->getMessage());
            }
        }

        Log::info("Job de blocage automatique terminé : {$comptesBloques} comptes bloqués");
    }
}
