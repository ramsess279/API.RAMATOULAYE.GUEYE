<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\TelephoneSenegalaisRule;
use App\Rules\CniSenegalaisRule;

class CompteRequest extends FormRequest
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
            // Informations compte
            'type' => 'required|in:epargne,cheque',
            'soldeInitial' => 'required|numeric|min:10000',
            'devise' => 'required|in:FCFA,EUR,USD',

            // Objet client
            'client' => 'required|array',
            'client.id' => 'nullable|exists:clients,id',
            'client.titulaire' => 'required|string|max:255',
            'client.nci' => ['nullable', new CniSenegalaisRule()],
            'client.email' => 'required|email',
            'client.telephone' => ['required', new TelephoneSenegalaisRule()],
            'client.adresse' => 'nullable|string|max:500',
        ];
    }

    /**
     * Messages d'erreurs personnalisés.
     */
    public function messages(): array
    {
        return [
            // Messages pour les informations compte
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être epargne ou cheque.',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être FCFA, EUR ou USD.',

            // Messages pour l'objet client
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un objet.',
            'client.id.exists' => 'Le client spécifié n\'existe pas.',
            'client.titulaire.required' => 'Le nom du titulaire est obligatoire.',
            'client.titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.nci.regex' => 'Le numéro CNI doit contenir exactement 13 chiffres.',
            'client.email.required' => 'L\'email est obligatoire.',
            'client.email.email' => 'L\'email doit être une adresse email valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }
}
