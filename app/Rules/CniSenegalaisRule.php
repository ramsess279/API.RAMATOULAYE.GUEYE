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
        // Si la valeur est vide ou null, ne pas valider
        if (empty($value)) {
            return;
        }

        // Le CNI sénégalais doit contenir exactement 13 chiffres
        if (!preg_match('/^[0-9]{13}$/', $value)) {
            $fail('Le numéro CNI doit contenir exactement 13 chiffres.');
        }

        // Pour la mise à jour, vérifier l'unicité en excluant le client actuel
        $numeroCompte = request()->route('numeroCompte');
        if ($numeroCompte) {
            // Si on a un numéro de compte, récupérer l'ID du client associé
            $compte = \App\Models\CompteModel::where('numeroCompte', $numeroCompte)->first();
            $clientId = $compte ? $compte->client_id : null;
        } else {
            $clientId = request()->route('clientId');
        }

        if ($clientId) {
            $exists = \App\Models\Client::where('cni', $value)
                                       ->where('id', '!=', $clientId)
                                       ->whereNotNull('cni')
                                       ->exists();
            if ($exists) {
                $fail('Ce numéro CNI est déjà enregistré.');
            }
        }
    }
}
