<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id, // Cast to string to ensure UUID format
            'numeroCompte' => $this->numeroCompte,
            'titulaire' => $this->client->nomComplet ?? 'N/A',
            'type' => $this->type,
            'solde' => $this->getSolde(),
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->statut === 'bloque' ? 'Inactivité de 30+ jours' : null,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1
            ]
        ];
    }
}