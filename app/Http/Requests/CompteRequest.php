<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'numeroCompte' => 'required|string|max:20|unique:comptes,numeroCompte',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * Messages d’erreurs personnalisés.
     */
    public function messages(): array
    {
        return [
            'numero_compte.required' => 'Le numéro de compte est obligatoire.',
            'numero_compte.unique' => 'Ce numéro de compte existe déjà.',
            'numero_compte.max' => 'Le numéro de compte ne peut pas dépasser 20 caractères.',
            'user_id.required' => 'L’identifiant de l’utilisateur est obligatoire.',
            'user_id.exists' => 'L’utilisateur spécifié n’existe pas.',
        ];
    }
}
