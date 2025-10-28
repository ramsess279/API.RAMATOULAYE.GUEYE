<?php

namespace App\Jobs;

use App\Models\CompteModel;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DesarchiverComptes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Démarrage du job de désarchivage des comptes');

        // Récupérer tous les comptes archivés qui sont maintenant actifs
        // (ceux qui ont été débloqués manuellement après archivage)
        $comptesArchives = CompteModel::where('archive', true)
            ->where('statut', 'actif')
            ->get();

        $comptesDesarchives = 0;

        foreach ($comptesArchives as $compte) {
            try {
                // Désarchiver le compte
                $compte->archive = false;
                $compte->dateArchivage = null;
                $compte->save();

                // Désarchiver toutes les transactions du compte
                Transaction::where('compte_id', $compte->id)
                    ->update(['archive' => false, 'dateArchivage' => null]);

                $comptesDesarchives++;
                Log::info("Compte {$compte->numeroCompte} désarchivé avec succès");

            } catch (\Exception $e) {
                Log::error("Erreur lors du désarchivage du compte {$compte->numeroCompte}: " . $e->getMessage());
            }
        }

        Log::info("Job de désarchivage terminé: {$comptesDesarchives} comptes désarchivés");
    }
}
