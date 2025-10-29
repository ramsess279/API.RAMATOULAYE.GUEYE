<?php

namespace App\Console\Commands;

use App\Models\CompteModel;
use Illuminate\Console\Command;

class AnnulerBlocageProgramme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comptes:annuler-programme {numeroCompte : Le numéro du compte}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Annuler le blocage programmé d\'un compte bancaire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $numeroCompte = $this->argument('numeroCompte');

        // Trouver le compte
        $compte = CompteModel::where('numeroCompte', $numeroCompte)->first();

        if (!$compte) {
            $this->error("Compte avec numéro {$numeroCompte} non trouvé");
            return 1;
        }

        // Vérifier qu'il y a un blocage programmé
        if (!$compte->dateBlocageProgramme) {
            $this->error("Aucun blocage programmé pour le compte {$numeroCompte}");
            return 1;
        }

        // Annuler le blocage programmé
        $compte->dateBlocageProgramme = null;
        $compte->dureeBlocageProgramme = null;
        $compte->uniteBlocageProgramme = null;
        $compte->motifBlocageProgramme = null;
        $compte->save();

        $this->info("Blocage programmé annulé pour le compte {$numeroCompte}");

        return 0;
    }
}
