<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'type' => $this->type,
            'description' => $this->description,
            'date_transaction' => $this->date_transaction?->format('Y-m-d H:i:s'),
            'compte' => [
                'id' => $this->compte->id,
                'numeroCompte' => $this->compte->numeroCompte,
                'type' => $this->compte->type,
                'devise' => $this->compte->devise,
                'solde' => $this->compte->solde,
                'statut' => $this->compte->statut,
            ],
            'client' => $this->whenLoaded('compte.client', function () {
                return [
                    'id' => $this->compte->client->id,
                    'nom' => $this->compte->client->nom,
                    'prenom' => $this->compte->client->prenom,
                    'email' => $this->compte->client->email,
                    'telephone' => $this->compte->client->telephone,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
