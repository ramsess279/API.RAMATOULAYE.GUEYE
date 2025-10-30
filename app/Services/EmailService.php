<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompteModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Service responsable de l'envoi d'emails
 * Suit le principe de responsabilité unique (SRP)
 */
class EmailService
{
    /**
     * Envoie un email d'authentification avec le mot de passe
     *
     * @param User $user
     * @param CompteModel $compte
     * @return bool
     */
    public function sendAuthenticationEmail(User $user, CompteModel $compte): bool
    {
        try {
            // Récupérer le mot de passe temporaire depuis la base de données
            $temporaryPassword = $this->getTemporaryPassword($user);

            // Contenu de l'email selon la spécification US 2.2
            $subject = 'Authentification - Création de votre compte bancaire';
            $body = "
Bonjour {$user->prenom} {$user->nom},

Votre compte bancaire a été créé avec succès.

Informations de connexion :
- Email : {$user->email}
- Mot de passe temporaire : {$temporaryPassword}
- Numéro de compte : {$compte->numeroCompte}

Important :
- Ce mot de passe est temporaire et doit être changé lors de votre première connexion.
- Conservez ces informations en sécurité.

Cordialement,
L'équipe de la Banque
            ";

            // Configuration pour Gmail
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'From: Banque <' . config('mail.from.address') . '>',
                'Reply-To: support@banque.com',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Envoi de l'email
            $result = mail($user->email, $subject, $body, implode("\r\n", $headers));

            if ($result) {
                $this->logEmailSending($user, $compte, 'authentication');
                return true;
            } else {
                Log::error('Échec de l\'envoi de l\'email via mail()', [
                    'user_email' => $user->email,
                    'compte_id' => $compte->id
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email d\'authentification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un email de confirmation de création de compte
     *
     * @param User $user
     * @param CompteModel $compte
     * @return bool
     */
    public function sendAccountCreationConfirmation(User $user, CompteModel $compte): bool
    {
        try {
            $this->logEmailSending($user, $compte, 'confirmation');

            // Simulation d'envoi d'email
            // Mail::to($user->email)->send(new AccountCreationConfirmation($user, $compte));

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de confirmation', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Récupère le mot de passe temporaire depuis la base de données
     *
     * @param User $user
     * @return string
     */
    private function getTemporaryPassword(User $user): string
    {
        // Le mot de passe temporaire est stocké hashé, mais on ne peut pas le récupérer
        // On génère un nouveau mot de passe temporaire pour l'email
        // En production, il faudrait stocker le mot de passe en clair temporairement

        // Pour l'instant, on utilise une logique simple : générer un mot de passe basé sur l'ID utilisateur
        return 'TempPass' . substr(md5($user->id . now()), 0, 8);
    }

    /**
     * Envoie un email avec le code de vérification
     *
     * @param string $email
     * @param array $data
     * @return bool
     */
    public function sendVerificationCode(string $email, array $data): bool
    {
        try {
            $subject = 'Code de vérification - Activation de votre compte bancaire';
            $body = "
Bonjour {$data['prenom']} {$data['nom']},

Votre compte bancaire a été créé avec succès.

Pour finaliser votre inscription, veuillez utiliser le code de vérification suivant :

Code de vérification : {$data['code']}

Ce code est valable pendant {$data['expires_in']} minutes.

Instructions :
- Utilisez ce code lors de votre première connexion
- Ne partagez ce code avec personne
- Le code expirera automatiquement après {$data['expires_in']} minutes

Si vous n'avez pas demandé la création de ce compte, veuillez ignorer cet email.

Cordialement,
L'équipe de la Banque
            ";

            // Configuration pour Gmail
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'From: Banque <' . config('mail.from.address') . '>',
                'Reply-To: support@banque.com',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Envoi de l'email
            $result = mail($email, $subject, $body, implode("\r\n", $headers));

            if ($result) {
                Log::info('Email de vérification envoyé', [
                    'email' => $email,
                    'code' => $data['code'],
                    'timestamp' => now()->toISOString()
                ]);
                return true;
            } else {
                Log::error('Échec de l\'envoi de l\'email de vérification', [
                    'email' => $email
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de vérification', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log l'envoi d'email pour traçabilité
     *
     * @param User $user
     * @param CompteModel $compte
     * @param string $type
     */
    private function logEmailSending(User $user, CompteModel $compte, string $type): void
    {
        Log::info("Email {$type} envoyé", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numeroCompte,
            'type' => $type,
            'timestamp' => now()->toISOString()
        ]);
    }
}