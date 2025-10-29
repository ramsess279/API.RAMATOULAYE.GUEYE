<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TelephoneSenegalaisRule;
use App\Rules\CniSenegalaisRule;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->getUserIdFromNumeroCompte();

        return [
            // Informations client - tous les champs sont optionnels mais au moins un requis
            'titulaire' => 'nullable|string|max:255',
            'informationsClient' => 'nullable|array',
            'informationsClient.telephone' => ['nullable', new TelephoneSenegalaisRule()],
            'informationsClient.email' => 'nullable|email' . ($userId ? '|unique:users,email,' . $userId : ''),
            'informationsClient.password' => 'nullable|string|min:8',
            'informationsClient.cni' => 'nullable|string|regex:/^[0-9]{13}$/', // CNI ne peut pas être modifié
        ];
    }

    /**
     * Messages d'erreurs personnalisés.
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être une adresse email valide.',
            'informationsClient.email.unique' => 'Cet email est déjà utilisé.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }

    /**
     * Validation personnalisée pour s'assurer qu'au moins un champ est fourni
     * et vérifier les conflits téléphone/email
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Vérifier si au moins un champ de modification est fourni
            $hasTitulaire = !empty($data['titulaire']);
            $hasClientInfo = isset($data['informationsClient']) &&
                            (!empty($data['informationsClient']['telephone']) ||
                             !empty($data['informationsClient']['email']) ||
                             !empty($data['informationsClient']['password']));

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }

            // Vérifier que le CNI n'est pas fourni (il ne peut pas être modifié)
            if (isset($data['informationsClient']['cni'])) {
                $validator->errors()->add('informationsClient.cni', 'Le numéro CNI ne peut pas être modifié.');
            }

            // Vérifier les conflits téléphone/email avec d'autres clients
            if (isset($data['informationsClient'])) {
                $this->validateNoConflicts($validator, $data['informationsClient']);
            }
        });
    }

    /**
     * Récupère l'ID utilisateur actuel du compte pour exclure de la validation unique
     */
    private function getUserIdFromNumeroCompte()
    {
        $numeroCompte = $this->route('numeroCompte');
        if ($numeroCompte) {
            $compte = \App\Models\CompteModel::where('numeroCompte', $numeroCompte)->first();
            if ($compte && $compte->client) {
                return $compte->client->user_id ?: null;
            }
        }
        return null;
    }

    /**
     * Valide qu'il n'y a pas de conflits avec téléphone/email d'autres clients
     */
    private function validateNoConflicts($validator, $clientData)
    {
        $numeroCompte = $this->route('numeroCompte');
        $currentCompte = \App\Models\CompteModel::where('numeroCompte', $numeroCompte)->first();

        if (!$currentCompte) {
            return;
        }

        // Vérifier téléphone
        if (!empty($clientData['telephone'])) {
            $existingUser = \App\Models\User::where('telephone', $clientData['telephone'])
                ->where('id', '!=', $currentCompte->client->user_id)
                ->first();
            if ($existingUser) {
                $validator->errors()->add('informationsClient.telephone', 'Ce numéro de téléphone est déjà utilisé par un autre client.');
            }
        }

        // Vérifier email
        if (!empty($clientData['email'])) {
            $existingUser = \App\Models\User::where('email', $clientData['email'])
                ->where('id', '!=', $currentCompte->client->user_id)
                ->first();
            if ($existingUser) {
                $validator->errors()->add('informationsClient.email', 'Cet email est déjà utilisé par un autre client.');
            }
        }
    }
}