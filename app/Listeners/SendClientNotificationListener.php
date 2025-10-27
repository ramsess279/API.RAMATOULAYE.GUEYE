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
        // Envoyer les notifications uniquement si c'est un nouveau client
        if ($event->isNewClient) {
            // Email d'authentification
            $this->emailService->sendAuthenticationEmail($event->client->user, $event->compte);

            // SMS avec le code d'authentification
            if ($event->client->code_auth) {
                $this->smsService->sendCodeSms($event->client->user, $event->client->code_auth);
            }
        }
    }
}
