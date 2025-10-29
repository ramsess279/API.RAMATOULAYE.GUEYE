<?php

namespace App\Console\Commands;

use App\Jobs\BloquerComptesProgramme;
use Illuminate\Console\Command;

class ExecuterBlocageProgramme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comptes:executer-blocage-programme {--date= : Date spécifique (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécuter le blocage automatique des comptes programmés pour aujourd\'hui';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ?: now()->toDateString();

        $this->info("Exécution du blocage automatique pour la date : {$date}");

        // Dispatcher le job
        BloquerComptesProgramme::dispatch();

        $this->info('Job de blocage automatique lancé avec succès');
        $this->info('Les comptes programmés pour cette date seront bloqués automatiquement');

        return 0;
    }
}
