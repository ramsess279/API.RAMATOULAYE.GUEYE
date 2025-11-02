<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
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
        $rules = [
            'compte_id' => 'required|uuid|exists:comptes,id',
            'montant' => 'required|numeric|min:0.01|max:999999999.99',
            'type' => 'required|in:depot,retrait,transfert',
            'description' => 'nullable|string|max:500',
            'date_transaction' => 'nullable|date|before_or_equal:today',
        ];

        // Additional validation for transfers
        if ($this->input('type') === 'transfert') {
            $rules['compte_destination_id'] = 'required|uuid|exists:comptes,id|different:compte_id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'compte_id.required' => 'Le compte est obligatoire.',
            'compte_id.exists' => 'Le compte sélectionné n\'existe pas.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'montant.max' => 'Le montant ne peut pas dépasser 999 999 999.99.',
            'type.required' => 'Le type de transaction est obligatoire.',
            'type.in' => 'Le type de transaction doit être depot, retrait ou transfert.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'date_transaction.date' => 'La date de transaction n\'est pas valide.',
            'date_transaction.before_or_equal' => 'La date de transaction ne peut pas être dans le futur.',
            'compte_destination_id.required' => 'Le compte de destination est obligatoire pour un transfert.',
            'compte_destination_id.exists' => 'Le compte de destination n\'existe pas.',
            'compte_destination_id.different' => 'Le compte de destination doit être différent du compte source.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'compte_id' => 'compte',
            'montant' => 'montant',
            'type' => 'type de transaction',
            'description' => 'description',
            'date_transaction' => 'date de transaction',
            'compte_destination_id' => 'compte de destination',
        ];
    }
}