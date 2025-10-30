<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Service de génération de codes de vérification
 */
class CodeGenerationService
{
    /**
     * Génère un code de vérification numérique de 6 chiffres
     */
    public function generateVerificationCode(): string
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Génère un code de vérification alphanumérique
     */
    public function generateAlphanumericCode(int $length = 8): string
    {
        return Str::random($length);
    }

    /**
     * Génère un code de vérification avec expiration
     */
    public function generateCodeWithExpiration(int $minutes = 15): array
    {
        return [
            'code' => $this->generateVerificationCode(),
            'expires_at' => now()->addMinutes($minutes)
        ];
    }
}