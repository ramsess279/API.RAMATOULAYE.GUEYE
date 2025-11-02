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
     * @param string $temporaryPassword
     * @return bool
     */
    public function sendAuthenticationEmail(User $user, CompteModel $compte, string $temporaryPassword, ?string $authenticationCode = null): bool
    {
        try {
            // Utiliser le code passé en paramètre, sinon récupérer depuis la base
            $codeAuth = $authenticationCode;
            if (!$codeAuth && $user->client) {
                if ($user->client->code_auth) {
                    $codeAuth = $user->client->code_auth;
                } else {
                    // Recharger depuis la base
                    $freshClient = \App\Models\Client::find($user->client->id);
                    $codeAuth = $freshClient ? $freshClient->code_auth : 'N/A';
                }
            }
            $codeAuth = $codeAuth ?: 'N/A';

            // Contenu de l'email selon la spécification US 2.2
            $subject = 'Informations de connexion - Création de votre compte bancaire';
            $body = "
Bonjour {$user->prenom} {$user->nom},

Votre compte bancaire a été créé avec succès.

INFORMATIONS DE CONNEXION :
- Email : {$user->email}
- Mot de passe temporaire : {$temporaryPassword}
- Code d'authentification : {$codeAuth}
- Numéro de compte : {$compte->numeroCompte}

INSTRUCTIONS IMPORTANTES :
- Utilisez ces informations pour votre première connexion
- Le mot de passe temporaire doit être changé lors de votre première connexion
- Le code d'authentification est requis pour valider votre compte
- Conservez ces informations en sécurité

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

            // Utiliser le mailer Laravel avec configuration de production
            try {
                \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($user, $subject) {
                    $message->to($user->email)
                            ->subject($subject)
                            ->from(config('mail.from.address'), config('mail.from.name'))
                            ->replyTo(config('mail.from.address'), config('mail.from.name'));
                });
                $result = true;
                Log::info('Email envoyé avec succès', [
                    'to' => $user->email,
                    'subject' => $subject,
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT_SET'
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email Laravel: ' . $e->getMessage(), [
                    'to' => $user->email,
                    'subject' => $subject,
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'trace' => $e->getTraceAsString()
                ]);
                $result = false;
            }

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

            // Utiliser le mailer Laravel avec configuration de production
            try {
                \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject)
                            ->from(config('mail.from.address'), config('mail.from.name'))
                            ->replyTo(config('mail.from.address'), config('mail.from.name'));
                });
                $result = true;
                Log::info('Email de vérification envoyé avec succès', [
                    'to' => $email,
                    'subject' => $subject,
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port')
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi email de vérification Laravel: ' . $e->getMessage(), [
                    'to' => $email,
                    'subject' => $subject,
                    'trace' => $e->getTraceAsString()
                ]);
                $result = false;
            }

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