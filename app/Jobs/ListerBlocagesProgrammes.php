<?php

namespace App\Console\Commands;

use App\Models\CompteModel;
use Illuminate\Console\Command;

class ListerBlocagesProgrammes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comptes:lister-programmes {--date= : Date spécifique (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lister tous les blocages de comptes programmés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');

        $query = CompteModel::with(['client.user'])
            ->whereNotNull('dateBlocageProgramme')
            ->orderBy('dateBlocageProgramme');

        if ($date) {
            $query->where('dateBlocageProgramme', $date);
        }

        $comptes = $query->get();

        if ($comptes->isEmpty()) {
            $this->info('Aucun blocage programmé trouvé.');
            return 0;
        }

        $this->info('Blocages de comptes programmés :');
        $this->line('================================');

        $headers = ['Numéro', 'Titulaire', 'Date blocage', 'Durée', 'Motif'];
        $rows = [];

        foreach ($comptes as $compte) {
            $rows[] = [
                $compte->numeroCompte,
                $compte->client->user->prenom . ' ' . $compte->client->user->nom,
                $compte->dateBlocageProgramme,
                $compte->dureeBlocageProgramme . ' ' . $compte->uniteBlocageProgramme,
                $compte->motifBlocageProgramme ?: 'N/A'
            ];
        }

        $this->table($headers, $rows);

        $this->info("Total : {$comptes->count()} blocage(s) programmé(s)");

        return 0;
    }
}
