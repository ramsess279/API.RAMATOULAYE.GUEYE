<?php

namespace App\Services;

/**
 * Service responsable de la génération des credentials (mot de passe et code)
 * Suit le principe de responsabilité unique (SRP)
 */
class CredentialGenerationService
{
    /**
     * Génère un mot de passe et un code d'authentification
     *
     * @return array ['password' => string, 'code' => string]
     */
    public function generateCredentials(): array
    {
        return [
            'password' => $this->generatePassword(),
            'code' => $this->generateCode(),
        ];
    }

    /**
     * Génère un mot de passe sécurisé
     *
     * @return string
     */
    private function generatePassword(): string
    {
        // Génère un mot de passe de 12 caractères avec lettres, chiffres et symboles
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $allChars = $uppercase . $lowercase . $numbers . $symbols;

        $password = '';

        // Assurer au moins un caractère de chaque type
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $symbols[rand(0, strlen($symbols) - 1)];

        // Compléter avec des caractères aléatoires
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }

        // Mélanger les caractères
        return str_shuffle($password);
    }

    /**
     * Génère un code numérique de 6 chiffres
     *
     * @return string
     */
    private function generateCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}