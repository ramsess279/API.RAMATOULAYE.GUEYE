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
        // Formats de téléphone sénégalais acceptés
        // +221771234567, +221781234567, +221761234567, +221701234567, etc.
        $pattern = '/^\+221(77|78|76|70|75|33)\d{7}$/';

        if (!preg_match($pattern, $value)) {
            $fail('Le numéro de téléphone doit être un numéro sénégalais valide (format: +221XXXXXXXXX).');
        }
    }
}
