<?php

namespace App\Listeners;

use App\Events\SendClientNotification;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendClientNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    private EmailService $emailService;
    private SmsService $smsService;

    /**
     * Create the event listener.
     */
    public function __construct(EmailService $emailService, SmsService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(SendClientNotification $event): void
    {
        // Attendre un court instant pour s'assurer que toutes les données sont sauvegardées
        sleep(2);

        // Envoyer les notifications uniquement si c'est un nouveau client
        if ($event->isNewClient) {
            // Recharger le client depuis la base pour avoir les dernières données
            $event->client->refresh();
            $event->client->user->refresh();

            // Debug: Afficher les données rechargées
            \Illuminate\Support\Facades\Log::info('Données dans le listener après refresh', [
                'client_code_auth' => $event->client->code_auth,
                'temporary_password' => $event->temporaryPassword,
                'authentication_code_event' => $event->authenticationCode,
                'user_email' => $event->client->user->email
            ]);

            // Email d'authentification avec le mot de passe temporaire et code depuis l'événement
            if ($event->temporaryPassword) {
                $this->emailService->sendAuthenticationEmail($event->client->user, $event->compte, $event->temporaryPassword, $event->authenticationCode);
            }

            // SMS avec le code d'authentification
            if ($event->client->code_auth) {
                $this->smsService->sendCodeSms($event->client->user, $event->client->code_auth);
            }
        }
    }
}
