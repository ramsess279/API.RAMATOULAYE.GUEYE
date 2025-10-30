<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service responsable de l'envoi de SMS
 * Suit le principe de responsabilité unique (SRP)
 */
class SmsService
{
    /**
     * Envoie un SMS avec le code d'authentification
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function sendCodeSms(User $user, string $code): bool
    {
        try {
            // Message SMS selon la spécification US 2.2
            $message = "BANQUE: Votre code d'authentification est: {$code}. Utilisez-le lors de votre première connexion.";

            $this->logSmsSending($user, $code, 'authentication_code');

            // Envoi réel avec AfricasTalking
            return $this->sendSms($user->telephone, $message);
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la création du compte
            Log::error('Erreur lors de l\'envoi du SMS', [
                'user_id' => $user->id,
                'telephone' => $user->telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie un SMS de confirmation de création de compte
     *
     * @param User $user
     * @param string $numeroCompte
     * @return bool
     */
    public function sendAccountCreationSms(User $user, string $numeroCompte): bool
    {
        try {
            $message = "Votre compte bancaire {$numeroCompte} a été créé avec succès.";

            $this->logSmsSending($user, $numeroCompte, 'account_creation');

            // Simulation d'envoi de SMS
            // $this->sendSms($user->telephone, $message);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS de confirmation', [
                'user_id' => $user->id,
                'telephone' => $user->telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie un SMS avec le code de vérification
     *
     * @param string $telephone
     * @param string $code
     * @return bool
     */
    public function sendVerificationCode(string $telephone, string $code): bool
    {
        try {
            $message = "BANQUE: Votre code de vérification est: {$code}. Valable 15 minutes.";

            // Envoi réel avec SMSMode
            return $this->sendSmsWithSmsMode($telephone, $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS de vérification', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Log l'envoi de SMS pour traçabilité
     *
     * @param User $user
     * @param string $content
     * @param string $type
     */
    private function logSmsSending(User $user, string $content, string $type): void
    {
        Log::info("SMS {$type} envoyé", [
            'user_id' => $user->id,
            'telephone' => $user->telephone,
            'type' => $type,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Méthode privée pour l'envoi réel de SMS avec HTTP API simple
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    private function sendSms(string $telephone, string $message): bool
    {
        try {
            $apiKey = config('services.africastalking.api_key');
            $username = config('services.africastalking.username');

            if (!$apiKey || !$username) {
                Log::warning('Configuration AfricasTalking manquante, SMS simulé', [
                    'telephone' => $telephone,
                    'message' => $message
                ]);
                return true; // Simulation en développement
            }

            // Utiliser l'API HTTP directe d'AfricasTalking
            $url = 'https://api.africastalking.com/version1/messaging';

            $data = [
                'username' => $username,
                'to' => $telephone,
                'message' => $message,
                'from' => null // Utilise le shortcode par défaut
            ];

            $headers = [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: ' . $apiKey
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pour développement

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                Log::error('Erreur cURL AfricasTalking', [
                    'error' => curl_error($ch),
                    'telephone' => $telephone
                ]);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 201 && isset($result['SMSMessageData']['Recipients'])) {
                Log::info('SMS AfricasTalking envoyé avec succès', [
                    'telephone' => $telephone,
                    'status' => $result['SMSMessageData']['Recipients'][0]['status'] ?? 'unknown',
                    'messageId' => $result['SMSMessageData']['Recipients'][0]['messageId'] ?? 'unknown'
                ]);
                return true;
            } else {
                Log::error('Erreur API AfricasTalking', [
                    'telephone' => $telephone,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception AfricasTalking SMS', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoie un SMS avec SMSMode
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    private function sendSmsWithSmsMode(string $telephone, string $message): bool
    {
        try {
            $accessToken = config('services.smsmode.access_token');
            $pseudo = config('services.smsmode.pseudo');

            if (!$accessToken || !$pseudo) {
                Log::warning('Configuration SMSMode manquante, SMS simulé', [
                    'telephone' => $telephone,
                    'message' => $message
                ]);
                return true; // Simulation en développement
            }

            // API SMSMode
            $url = 'https://rest.smsmode.com/sms/send';

            // Nettoyer le numéro de téléphone (enlever +221 pour le Sénégal)
            $cleanPhone = preg_replace('/^\+221/', '', $telephone);
            $cleanPhone = preg_replace('/^221/', '', $cleanPhone);

            $data = [
                'recipient' => '221' . $cleanPhone, // Format international requis
                'body' => $message,
                'sender' => 'BANQUE' // Nom de l'expéditeur
            ];

            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Api-Key: ' . $accessToken
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pour développement

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                Log::error('Erreur cURL SMSMode', [
                    'error' => curl_error($ch),
                    'telephone' => $telephone
                ]);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 200 && isset($result['smsId'])) {
                Log::info('SMS SMSMode envoyé avec succès', [
                    'telephone' => $telephone,
                    'smsId' => $result['smsId']
                ]);
                return true;
            } else {
                Log::error('Erreur API SMSMode', [
                    'telephone' => $telephone,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception SMSMode SMS', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}