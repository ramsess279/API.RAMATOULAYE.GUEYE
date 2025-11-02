<?php

namespace App\Services;

class ValidationService
{
    /**
     * Validate email uniqueness
     */
    public static function validateEmailUnique(string $email, ?int $excludeUserId = null): bool
    {
        $query = \App\Models\User::where('email', $email);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return !$query->exists();
    }

    /**
     * Validate phone uniqueness
     */
    public static function validatePhoneUnique(?string $phone, ?int $excludeUserId = null): bool
    {
        if (!$phone) return true;
        $query = \App\Models\User::where('telephone', $phone);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return !$query->exists();
    }

    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): bool
    {
        return strlen($password) >= 8;
    }

    /**
     * Validate date format and range
     */
    public static function validateDate(string $date, string $minDate = '1900-01-01', string $maxDate = 'today'): array
    {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            return ['valid' => false, 'message' => 'Format de date invalide'];
        }

        $minDateObj = new \DateTime($minDate);
        $maxDateObj = new \DateTime($maxDate);

        if ($dateObj < $minDateObj) {
            return ['valid' => false, 'message' => 'Date trop ancienne'];
        }

        if ($dateObj > $maxDateObj) {
            return ['valid' => false, 'message' => 'Date dans le futur'];
        }

        return ['valid' => true];
    }

    /**
     * Validate CNI format and uniqueness
     */
    public static function validateCni(string $cni, ?string $excludeClientId = null): array
    {
        if (!preg_match('/^\d{13}$/', $cni)) {
            return ['valid' => false, 'message' => 'Le numéro de CNI doit contenir exactement 13 chiffres'];
        }

        $query = \App\Models\Client::where('cni', $cni);
        if ($excludeClientId) {
            $query->where('id', '!=', $excludeClientId);
        }

        if ($query->exists()) {
            return ['valid' => false, 'message' => 'Ce numéro de CNI est déjà enregistré'];
        }

        return ['valid' => true];
    }

    /**
     * Validate employee number uniqueness
     */
    public static function validateEmployeeNumber(string $numeroEmploye, ?string $excludeAdminId = null): bool
    {
        $query = \App\Models\Admin::where('numero_employe', $numeroEmploye);
        if ($excludeAdminId) {
            $query->where('id', '!=', $excludeAdminId);
        }
        return !$query->exists();
    }

    /**
     * Validate enum values
     */
    public static function validateEnum(?string $value, array $allowedValues): bool
    {
        return $value === null || in_array($value, $allowedValues);
    }

    /**
     * Validate transaction
     */
    public function validateTransaction(\App\Models\CompteModel $compte, array $transactionData): void
    {
        // Check if account is active
        if ($compte->statut !== 'actif') {
            throw new \Exception('Le compte n\'est pas actif. Impossible de réaliser la transaction.');
        }

        $montant = $transactionData['montant'];
        $type = $transactionData['type'];

        switch ($type) {
            case 'retrait':
                if ($compte->solde < $montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce retrait.');
                }
                break;

            case 'transfert':
                if ($compte->solde < $montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce transfert.');
                }
                // Additional validation for destination account would go here
                break;

            case 'depot':
                // Deposits are always allowed for active accounts
                break;

            default:
                throw new \Exception('Type de transaction non valide.');
        }

        // Check daily transaction limits (example: max 5M per day)
        $todayTransactions = $compte->transactions()
            ->whereDate('date_transaction', today())
            ->sum('montant');

        if (($todayTransactions + $montant) > 5000000) { // 5M FCFA limit
            throw new \Exception('Limite de transactions journalière dépassée.');
        }
    }
}