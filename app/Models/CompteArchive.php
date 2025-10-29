<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidTrait;

class CompteArchive extends Model
{
    use HasFactory, UuidTrait;

    protected $connection = 'archive';
    protected $table = 'comptes_archive';

    protected $fillable = [
        'id',
        'numeroCompte',
        'type',
        'devise',
        'statut',
        'client_nom',
        'client_prenom',
        'client_email',
        'client_telephone',
        'client_cni',
        'solde',
        'dateBlocage',
        'dateDeblocagePrevue',
        'motifBlocage',
        'dureeBlocage',
        'uniteBlocage',
        'dureeBlocageJours',
        'dateArchivage'
    ];

    protected $dates = [
        'dateBlocage',
        'dateDeblocagePrevue',
        'dateArchivage',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'dureeBlocage' => 'integer',
        'dureeBlocageJours' => 'integer',
        'dateArchivage' => 'datetime',
    ];

    /**
     * Scope pour rechercher par numéro de compte
     */
    public function scopeNumero($query, string $numero)
    {
        return $query->where('numeroCompte', $numero);
    }

    /**
     * Scope pour rechercher par email client
     */
    public function scopeClientEmail($query, string $email)
    {
        return $query->where('client_email', $email);
    }

    /**
     * Scope pour rechercher par téléphone client
     */
    public function scopeClientTelephone($query, string $telephone)
    {
        return $query->where('client_telephone', $telephone);
    }

    /**
     * Scope pour filtrer par type de compte
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }
}
