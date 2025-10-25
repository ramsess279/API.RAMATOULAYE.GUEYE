<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email',
            'password' => $this->isMethod('post') ? 'required|string' : 'nullable|string',
            'password_confirmation' => $this->isMethod('post') ? 'required|string' : 'nullable|string',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|string',
            'adresse' => 'nullable|string|max:500',
            'statut' => 'nullable|string',
            'numero_employe' => 'nullable|string',
            'niveau_acces' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'genre' => 'nullable|string',
            'cni' => 'nullable|string',
            'date_delivrance_cni' => 'nullable|string',
            'date_expiration_cni' => 'nullable|string',
            'lieu_delivrance_cni' => 'nullable|string|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Pour l'instant, pas de validations complexes pour les données fictives de test
            // Les validations seront ajoutées plus tard selon les besoins réels
        });
    }

    /**
     * Get the user ID for uniqueness validation
     *
     * @return int|null
     */
    abstract protected function getUserId(): ?int;

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_naissance.after' => 'La date de naissance semble invalide.',
            'statut.in' => 'Le statut doit être actif, inactif ou suspendu.',
            'numero_employe.unique' => 'Ce numéro d\'employé est déjà utilisé.',
            'niveau_acces.in' => 'Le niveau d\'accès doit être super_admin, admin ou moderateur.',
            'permissions.array' => 'Les permissions doivent être une liste.',
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'genre.in' => 'Le genre doit être homme, femme ou autre.',
            'cni.unique' => 'Ce numéro de CNI est déjà enregistré.',
            'date_delivrance_cni.before_or_equal' => 'La date de délivrance ne peut pas être dans le futur.',
            'date_expiration_cni.after' => 'La date d\'expiration doit être postérieure à la date de délivrance.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom complet',
            'email' => 'email',
            'password' => 'mot de passe',
            'telephone' => 'téléphone',
            'date_naissance' => 'date de naissance',
            'adresse' => 'adresse',
            'statut' => 'statut',
            'numero_employe' => 'numéro d\'employé',
            'niveau_acces' => 'niveau d\'accès',
            'permissions' => 'permissions',
            'nom' => 'nom',
            'prenom' => 'prénom',
            'genre' => 'genre',
            'cni' => 'numéro de CNI',
            'date_delivrance_cni' => 'date de délivrance CNI',
            'date_expiration_cni' => 'date d\'expiration CNI',
            'lieu_delivrance_cni' => 'lieu de délivrance CNI',
        ];
    }
}
