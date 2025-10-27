<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneSenegalaisRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si la valeur est vide ou null, ne pas valider
        if (empty($value)) {
            return;
        }

        // Formats de téléphone sénégalais acceptés
        // +221771234567, +221781234567, +221761234567, +221701234567, etc.
        $pattern = '/^\+221(77|78|76|70|75|33)\d{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (format: +221XXXXXXXXX).');
        }

        // Pour la mise à jour, vérifier l'unicité en excluant le client actuel
        $clientId = request()->route('clientId');
        $client = \App\Models\Client::find($clientId);
        if ($client) {
            $exists = \App\Models\User::where('telephone', $value)
                                     ->where('id', '!=', $client->user_id)
                                     ->exists();
            if ($exists) {
                $fail('Ce numéro de téléphone est déjà utilisé.');
            }
        }
    }
}
