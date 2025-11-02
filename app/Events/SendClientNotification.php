<?php

namespace App\Events;

use App\Models\Client;
use App\Models\CompteModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendClientNotification
{
    use Dispatchable, SerializesModels;

    public Client $client;
    public CompteModel $compte;
    public bool $isNewClient;
    public ?string $temporaryPassword;
    public ?string $authenticationCode;

    /**
     * Create a new event instance.
     *
     * @param Client $client
     * @param CompteModel $compte
     * @param bool $isNewClient
     * @param string|null $temporaryPassword
     * @param string|null $authenticationCode
     */
    public function __construct(Client $client, CompteModel $compte, bool $isNewClient = false, ?string $temporaryPassword = null, ?string $authenticationCode = null)
    {
        $this->client = $client;
        $this->compte = $compte;
        $this->isNewClient = $isNewClient;
        $this->temporaryPassword = $temporaryPassword;
        $this->authenticationCode = $authenticationCode;
    }
}
