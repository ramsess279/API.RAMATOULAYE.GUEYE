<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'dateNaissance',
        'adresse',
        'genre',
        'statut',
        'cni',
        'date_delivrance_cni',
        'date_expiration_cni',
        'lieu_delivrance_cni'
    ];

    protected $casts = [
        'dateNaissance' => 'date',
        'statut' => 'string',
        'genre' => 'string',
        'date_delivrance_cni' => 'datetime',
        'date_expiration_cni' => 'datetime'
    ];

    /**
     * Relation avec l'utilisateur de base
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec les comptes bancaires
     */
    public function comptes(): HasMany
    {
        return $this->hasMany(CompteModel::class, 'client_id');
    }

    /**
     * Retourne le nom complet du client
     */
    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Vérifie si la CNI est expirée
     */
    public function isCniExpired(): bool
    {
        return $this->date_expiration_cni && $this->date_expiration_cni->isPast();
    }

    /**
     * Vérifie si la CNI est valide
     */
    public function isCniValid(): bool
    {
        return $this->cni && !$this->isCniExpired();
    }
}
