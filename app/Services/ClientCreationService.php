<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Services\CodeGenerationService;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Service responsable de la vérification et création de clients
 * Suit le principe de responsabilité unique (SRP)
 */
class ClientCreationService
{
    private bool $clientNewlyCreated = false;
    private CodeGenerationService $codeGenerationService;
    private EmailService $emailService;
    private SmsService $smsService;

    public function __construct(
        CodeGenerationService $codeGenerationService,
        EmailService $emailService,
        SmsService $smsService
    ) {
        $this->codeGenerationService = $codeGenerationService;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    /**
     * Vérifie si un client existe ou le crée s'il n'existe pas
     * Vérifie la cohérence des données uniques (CNI, téléphone, email)
     *
     * @param array $data Données du client
     * @return Client Le client trouvé ou créé
     * @throws \Exception Si les données sont incohérentes
     */
    public function getOrCreateClient(array $data): Client
    {
        $clientData = $data['client'];

        // Vérifier que tous les champs obligatoires sont présents
        $this->validateRequiredFields($clientData);

        // LOGIQUE PRINCIPALE : Recherche par CNI uniquement
        if (!empty($clientData['cni'])) {
            $client = Client::where('cni', $clientData['cni'])->with('user')->first();
            if ($client) {
                // CNI existe : on utilise les données de la base, on ignore celles de la requête
                $this->clientNewlyCreated = false;
                return $client;
            }
        }

        // CNI n'existe pas : vérifier que téléphone et email ne sont pas déjà liés à un autre CNI
        $this->validateNoExistingClientWithPhoneOrEmail($clientData);

        // Aucun client trouvé, créer un nouveau
        $user = $this->createUser($clientData);
        $client = $this->createClient($user, $clientData);

        // NE PAS envoyer le code de vérification ici - il sera envoyé plus tard avec les credentials complets
        // $this->generateAndSendVerificationCode($user);

        $this->clientNewlyCreated = true;
        return $client;
    }

    /**
     * Crée un nouvel utilisateur
     *
     * @param array $data
     * @return User
     */
    private function createUser(array $data): User
    {
        // Extraire nom et prénom du titulaire
        $nomPrenom = $this->extractNomPrenom($data['titulaire']);

        return User::create([
            'nom' => $nomPrenom['nom'],
            'prenom' => $nomPrenom['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'dateNaissance' => $data['dateNaissance'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'genre' => $data['genre'] ?? null,
            'role' => 'client',
            // Le mot de passe sera défini plus tard par le service de génération de credentials
            'password' => Hash::make('temporary_password_' . time()), // Mot de passe temporaire
        ]);
    }

    /**
     * Crée un nouveau client
     *
     * @param User $user
     * @param array $data
     * @return Client
     */
    private function createClient(User $user, array $data): Client
    {
        return Client::create([
            'user_id' => $user->id,
            'statut' => 'actif',
            'cni' => $data['cni'] ?? null,
            'date_delivrance_cni' => $data['date_delivrance_cni'] ?? null,
            'date_expiration_cni' => $data['date_expiration_cni'] ?? null,
            'lieu_delivrance_cni' => $data['lieu_delivrance_cni'] ?? null,
        ]);
    }

    /**
     * Indique si le client a été créé lors de cette opération
     *
     * @return bool
     */
    public function isClientNewlyCreated(): bool
    {
        return $this->clientNewlyCreated;
    }

    /**
     * Vérifie que tous les champs obligatoires sont présents
     *
     * @param array $clientData
     * @throws \Exception
     */
    private function validateRequiredFields(array $clientData): void
    {
        $requiredFields = ['titulaire', 'email', 'telephone', 'cni'];
        foreach ($requiredFields as $field) {
            if (empty($clientData[$field])) {
                throw new \Exception("Le champ '{$field}' est obligatoire.");
            }
        }
    }

    /**
     * Valide qu'aucun client existant n'a déjà ce téléphone ou email (pour éviter les conflits)
     *
     * @param array $clientData
     * @throws \Exception
     */
    private function validateNoExistingClientWithPhoneOrEmail(array $clientData): void
    {
        // Vérifier si le téléphone existe déjà
        $existingUserByPhone = User::where('telephone', $clientData['telephone'])->first();
        if ($existingUserByPhone) {
            throw new \Exception("ERREUR VALIDATION: Le numéro de téléphone '{$clientData['telephone']}' est déjà utilisé dans le système.");
        }

        // Vérifier si l'email existe déjà
        $existingUserByEmail = User::where('email', $clientData['email'])->first();
        if ($existingUserByEmail) {
            throw new \Exception("ERREUR VALIDATION: L'adresse email '{$clientData['email']}' est déjà utilisée dans le système.");
        }
    }

    /**
     * Génère et envoie le code de vérification au nouveau client
     *
     * @param User $user
     */
    private function generateAndSendVerificationCode(User $user): void
    {
        // Générer le code de vérification
        $codeData = $this->codeGenerationService->generateCodeWithExpiration(15); // 15 minutes

        // Sauvegarder le code dans la base de données
        $user->update([
            'verification_code' => $codeData['code'],
            'verification_code_expires_at' => $codeData['expires_at'],
            'is_verified' => false
        ]);

        // Envoyer le code par email
        try {
            $this->emailService->sendVerificationCode($user->email, [
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'code' => $codeData['code'],
                'expires_in' => 15
            ]);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas interrompre le processus
            Log::error('Erreur envoi email code vérification: ' . $e->getMessage());
        }

        // Envoyer le code par SMS
        try {
            $this->smsService->sendVerificationCode($user->telephone, $codeData['code']);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas interrompre le processus
            Log::error('Erreur envoi SMS code vérification: ' . $e->getMessage());
        }
    }

    /**
     * Extrait le nom et prénom du titulaire
     *
     * @param string $titulaire
     * @return array
     */
    private function extractNomPrenom(string $titulaire): array
    {
        $parts = explode(' ', trim($titulaire));
        $prenom = array_shift($parts);
        $nom = implode(' ', $parts);

        return [
            'nom' => $nom ?: $prenom, // Si pas de nom, utiliser le prénom comme nom
            'prenom' => $prenom
        ];
    }
}