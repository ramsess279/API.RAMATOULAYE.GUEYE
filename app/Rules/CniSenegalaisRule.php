<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CniSenegalaisRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Le CNI sénégalais doit contenir exactement 13 chiffres
        if (!preg_match('/^[0-9]{13}$/', $value)) {
            $fail('Le numéro CNI doit contenir exactement 13 chiffres.');
        }

        // Vérifier l'unicité du CNI
        $exists = \App\Models\Client::where('cni', $value)->exists();
        if ($exists) {
            $fail('Ce numéro CNI est déjà enregistré.');
        }
    }
}
