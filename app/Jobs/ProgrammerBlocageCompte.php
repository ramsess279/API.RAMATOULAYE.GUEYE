<?php

namespace App\Console\Commands;

use App\Models\CompteModel;
use Illuminate\Console\Command;

class ProgrammerBlocageCompte extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comptes:programmer-blocage
                            {numeroCompte : Le numéro du compte à programmer}
                            {dateBlocage : Date de blocage (YYYY-MM-DD)}
                            {duree : Durée du blocage}
                            {unite : Unité (jours|mois)}
                            {--motif= : Motif du blocage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Programmer le blocage automatique d\'un compte bancaire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $numeroCompte = $this->argument('numeroCompte');
        $dateBlocage = $this->argument('dateBlocage');
        $duree = (int) $this->argument('duree');
        $unite = $this->argument('unite');
        $motif = $this->option('motif') ?: 'Blocage programmé';

        // Validation des arguments
        if (!in_array($unite, ['jours', 'mois'])) {
            $this->error('L\'unité doit être "jours" ou "mois"');
            return 1;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateBlocage)) {
            $this->error('La date doit être au format YYYY-MM-DD');
            return 1;
        }

        // Vérifier que la date est dans le futur
        if ($dateBlocage <= now()->toDateString()) {
            $this->error('La date de blocage doit être dans le futur');
            return 1;
        }

        // Trouver le compte
        $compte = CompteModel::where('numeroCompte', $numeroCompte)->first();

        if (!$compte) {
            $this->error("Compte avec numéro {$numeroCompte} non trouvé");
            return 1;
        }

        // Vérifier que c'est un compte épargne actif
        if ($compte->type !== 'epargne') {
            $this->error('Seuls les comptes épargne peuvent être programmés pour blocage');
            return 1;
        }

        if ($compte->statut !== 'actif') {
            $this->error('Seuls les comptes actifs peuvent être programmés pour blocage');
            return 1;
        }

        // Programmer le blocage
        $compte->dateBlocageProgramme = $dateBlocage;
        $compte->dureeBlocageProgramme = $duree;
        $compte->uniteBlocageProgramme = $unite;
        $compte->motifBlocageProgramme = $motif;
        $compte->save();

        $this->info("Blocage programmé pour le compte {$numeroCompte}");
        $this->info("Date de blocage : {$dateBlocage}");
        $this->info("Durée : {$duree} {$unite}");
        $this->info("Motif : {$motif}");

        return 0;
    }
}
