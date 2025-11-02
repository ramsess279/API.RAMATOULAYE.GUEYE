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
     * Envoie un SMS avec le code d'authentification et les informations de connexion (simulation uniquement)
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function sendCodeSms(User $user, string $code): bool
    {
        try {
            // Récupérer le mot de passe temporaire depuis le client
            $temporaryPassword = $user->client ? $user->client->temporaryPassword : 'N/A';

            // Récupérer le numéro de compte
            $numeroCompte = 'N/A';
            if ($user->client && $user->client->compte) {
                $numeroCompte = $user->client->compte->numeroCompte;
            }

            // Message SMS complet avec toutes les informations
            $message = "BANQUE: Compte créé! Email: {$user->email}, MDP temp: {$temporaryPassword}, Code auth: {$code}, Num compte: {$numeroCompte}";

            $this->logSmsSending($user, $code, 'authentication_code');

            // Simulation uniquement pour les tests
            return $this->simulateSms($user, $message);
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
     * Simule l'envoi d'un SMS pour les tests
     *
     * @param User $user
     * @param string $message
     * @return bool
     */
    private function simulateSms(User $user, string $message): bool
    {
        Log::info('SMS simulé (AfricasTalking non disponible)', [
            'to' => $user->telephone,
            'message' => $message,
            'user_id' => $user->id
        ]);

        return true; // Simulation réussie
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
     * Envoie un SMS avec le code de vérification (simulation uniquement)
     *
     * @param string $telephone
     * @param string $code
     * @return bool
     */
    public function sendVerificationCode(string $telephone, string $code): bool
    {
        try {
            $message = "BANQUE: Votre code de vérification est: {$code}. Valable 15 minutes.";

            // Simulation uniquement pour les tests
            return $this->simulateSmsForTest($telephone, $message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS de vérification', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Simulation d'envoi SMS pour les tests (sans consommer de crédits)
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    private function simulateSmsForTest(string $telephone, string $message): bool
    {
        Log::info('SMS simulé (test gratuit)', [
            'to' => $telephone,
            'message' => $message,
            'note' => 'Simulation pour économiser les crédits de test'
        ]);

        return true; // Simulation réussie
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
     * Envoie un SMS avec Twilio (utilise les crédits de test gratuits)
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    private function sendSmsWithTwilio(string $telephone, string $message): bool
    {
        try {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from = config('services.twilio.from');

            if (!$sid || !$token || !$from) {
                Log::warning('Configuration Twilio manquante', [
                    'telephone' => $telephone,
                    'message' => $message
                ]);
                return false;
            }

            // Utiliser l'API Twilio
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            $data = [
                'From' => $from,
                'To' => $telephone,
                'Body' => $message
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                Log::error('Erreur cURL Twilio', [
                    'error' => curl_error($ch),
                    'telephone' => $telephone
                ]);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 201 && isset($result['sid'])) {
                Log::info('SMS Twilio envoyé avec succès (crédits de test)', [
                    'telephone' => $telephone,
                    'messageSid' => $result['sid'],
                    'message' => $message
                ]);
                return true;
            } else {
                Log::error('Erreur API Twilio', [
                    'telephone' => $telephone,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception Twilio SMS', [
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

            // API SMSMode - URL corrigée selon la documentation
            $url = 'https://api.smsmode.com/http/1.6/sendSMS.do';

            // Nettoyer le numéro de téléphone (enlever +221 pour le Sénégal)
            $cleanPhone = preg_replace('/^\+221/', '', $telephone);
            $cleanPhone = preg_replace('/^221/', '', $cleanPhone);

            // Format des paramètres pour SMSMode HTTP API
            $params = [
                'accessToken' => $accessToken,
                'message' => $message,
                'numero' => '221' . $cleanPhone,
                'emetteur' => 'BANQUE',
                'stop' => 0
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

            // Pour SMSMode, une réponse réussie contient généralement un ID ou un code de succès
            if ($httpCode === 200) {
                $responseContent = trim($response);
                // SMSMode retourne généralement un ID numérique ou un code de succès
                if (is_numeric($responseContent) || strpos($responseContent, 'OK') !== false) {
                    Log::info('SMS SMSMode envoyé avec succès', [
                        'telephone' => $telephone,
                        'response' => $responseContent
                    ]);
                    return true;
                }
            }

            Log::error('Erreur API SMSMode', [
                'telephone' => $telephone,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception SMSMode SMS', [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}