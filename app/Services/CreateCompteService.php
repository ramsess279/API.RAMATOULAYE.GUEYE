<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompteModel;
use App\Models\User;
use App\Services\ClientCreationService;
use App\Services\CredentialGenerationService;
use App\Services\EmailService;
use App\Services\SmsService;
use App\Models\Transaction;
use App\Events\SendClientNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service responsable de la création d'un nouveau compte bancaire
 * Suit le principe de responsabilité unique (SRP)
 */
class CreateCompteService
{
    private ClientCreationService $clientCreationService;
    private CredentialGenerationService $credentialGenerationService;
    private EmailService $emailService;
    private SmsService $smsService;

    public function __construct(
        ClientCreationService $clientCreationService,
        CredentialGenerationService $credentialGenerationService,
        EmailService $emailService,
        SmsService $smsService
    ) {
        $this->clientCreationService = $clientCreationService;
        $this->credentialGenerationService = $credentialGenerationService;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    /**
     * Crée un nouveau compte bancaire selon les spécifications de l'US 2.2
     *
     * @param array $data Données du compte à créer
     * @return CompteModel Le compte créé
     * @throws \Exception En cas d'erreur lors de la création
     */
    public function createCompte(array $data): CompteModel
    {
        return DB::transaction(function () use ($data) {
            // Étape 1: Vérifier/Créer le client
            $client = $this->clientCreationService->getOrCreateClient($data);

            // Étape 2: Générer les credentials si client nouveau
            if ($this->clientCreationService->isClientNewlyCreated()) {
                $credentials = $this->credentialGenerationService->generateCredentials();

                // Mettre à jour le mot de passe de l'utilisateur
                $client->user->update([
                    'password' => Hash::make($credentials['password'])
                ]);

                // Stocker le code temporaire pour la première connexion
                $client->update([
                    'code_auth' => $credentials['code'],
                    'code_expires_at' => now()->addHours(24) // Code valide 24h
                ]);

                // Générer et définir le code de vérification utilisateur (pour l'authentification)
                $verificationCodeData = app(CodeGenerationService::class)->generateCodeWithExpiration(15);
                $client->user->update([
                    'verification_code' => $verificationCodeData['code'],
                    'verification_code_expires_at' => $verificationCodeData['expires_at'],
                    'is_verified' => false
                ]);

                // Debug: Afficher les codes générés
                \Illuminate\Support\Facades\Log::info('Codes générés pour nouveau client', [
                    'email' => $client->user->email,
                    'code_auth_client' => $credentials['code'],
                    'verification_code_user' => $verificationCodeData['code'],
                    'temporary_password' => $credentials['password']
                ]);
            }

            // Étape 3: Créer le compte
            $compte = $this->createCompteRecord($client, $data);

            // Étape 4: Créer la transaction de dépôt initiale
            $this->createInitialDepositTransaction($compte, $data['soldeInitial']);

            // Étape 5: Déclencher l'événement de notification uniquement pour les nouveaux clients
             if ($this->clientCreationService->isClientNewlyCreated()) {
                 // S'assurer que les credentials existent (générés à l'étape 2)
                 if (!isset($credentials)) {
                     $credentials = $this->credentialGenerationService->generateCredentials();
                 }

                 $temporaryPassword = $credentials['password'];
                 $authenticationCode = $credentials['code'];

                 // Ajouter les credentials à la réponse
                 $compte->temporaryPassword = $temporaryPassword;
                 $compte->authenticationCode = $authenticationCode;

                 // Créer l'événement avec les credentials APRÈS avoir tout sauvegardé
                 event(new SendClientNotification($client, $compte, true, $temporaryPassword, $authenticationCode));
             }

            return $compte;
        });
    }

    /**
     * Retourne le service de création de client pour vérifications externes
     *
     * @return ClientCreationService
     */
    public function getClientCreationService(): ClientCreationService
    {
        return $this->clientCreationService;
    }

    /**
     * Crée l'enregistrement du compte en base de données
     *
     * @param Client $client
     * @param array $data
     * @return CompteModel
     */
    private function createCompteRecord(Client $client, array $data): CompteModel
    {
        return CompteModel::create([
            'client_id' => $client->id,
            'type' => $data['type'] ?? 'epargne',
            'statut' => 'actif',
            'devise' => $data['devise'] ?? 'FCFA',
            // Le numéro de compte sera généré automatiquement via le modèle
        ]);
    }

    /**
     * Crée la transaction de dépôt initiale
     *
     * @param CompteModel $compte
     * @param float $montant
     */
    private function createInitialDepositTransaction(CompteModel $compte, float $montant): void
    {
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => $montant,
            'description' => 'Dépôt initial lors de la création du compte',
            'date_transaction' => now(),
            'statut' => 'effectue',
        ]);
    }

}