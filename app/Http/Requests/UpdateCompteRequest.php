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
        return [
            // Informations client - tous les champs sont optionnels mais au moins un requis
            'titulaire' => 'nullable|string|max:255',
            'informationsClient' => 'nullable|array',
            'informationsClient.telephone' => ['nullable', new TelephoneSenegalaisRule()],
            'informationsClient.email' => 'nullable|email',
            'informationsClient.password' => 'nullable|string|min:8',
            'informationsClient.cni' => ['nullable', new CniSenegalaisRule()],
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
                            !empty($data['informationsClient']['password']) ||
                            !empty($data['informationsClient']['cni']));

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Au moins un champ de modification doit être fourni.');
            }
        });
    }
}