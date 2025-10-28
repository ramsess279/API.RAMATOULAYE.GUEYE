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

class ArchiverComptesBloques implements ShouldQueue
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
        Log::info('Démarrage du job d\'archivage des comptes bloqués expirés');

        // Récupérer tous les comptes bloqués dont la date de déblocage prévue est dépassée
        $comptesExpires = CompteModel::where('statut', 'bloque')
            ->where('dateDeblocagePrevue', '<', now())
            ->where('archive', false)
            ->get();

        $comptesArchives = 0;

        foreach ($comptesExpires as $compte) {
            try {
                // Archiver le compte
                $compte->archive = true;
                $compte->dateArchivage = now();
                $compte->save();

                // Archiver toutes les transactions du compte
                Transaction::where('compte_id', $compte->id)
                    ->update(['archive' => true, 'dateArchivage' => now()]);

                $comptesArchives++;
                Log::info("Compte {$compte->numeroCompte} archivé avec succès");

            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage du compte {$compte->numeroCompte}: " . $e->getMessage());
            }
        }

        Log::info("Job d'archivage terminé: {$comptesArchives} comptes archivés");
    }
}
