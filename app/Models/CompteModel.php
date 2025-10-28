<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\GenerateNumeroCompte;
use App\Services\CalculSoldeService;
use App\Traits\UuidTrait;

class CompteModel extends Model
{
       use HasFactory, UuidTrait, SoftDeletes;

    protected $table = "comptes";

    protected $fillable = [
        "id",
        "numeroCompte",
        "type",
        "statut",
        "client_id",
        "devise"
    ];

    protected $dates = ['deleted_at'];

    protected $appends = [
        'solde'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    /**
     * Relation avec le client propriétaire du compte
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Calcule et retourne le solde du compte basé sur les transactions
     *
     * @return float
     */
    public function getSolde(): float
    {
        $calculSoldeService = new CalculSoldeService();
        return $calculSoldeService->calculerSolde($this);
    }

    /**
     * Accesseur pour le solde (pour compatibilité avec l'ancien code)
     *
     * @return float
     */
    public function getSoldeAttribute(): float
    {
        return $this->getSolde();
    }

    /**
     * Scope global pour récupérer uniquement les comptes non supprimés
     * Les comptes sont considérés comme supprimés s'ils ont un statut 'ferme' ou sont soft deleted
     */
    protected static function booted()
    {
        static::addGlobalScope('nonSupprimes', function (Builder $builder) {
            $builder->where('statut', '!=', 'ferme')->whereNull('deleted_at');
        });

        static::creating(function ($compte) {
            if (empty($compte->numeroCompte)) {
                $generator = new GenerateNumeroCompte();
                $compte->numeroCompte = $generator->generate();
            }
        });

        // Supprimé l'événement created pour éviter les conflits avec les factories
        // Les transactions seront générées manuellement dans les seeders
    }

    /**
     * Scope local pour récupérer un compte par son numéro
     *
     * @param Builder $query
     * @param string $numero
     * @return Builder
     */
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numeroCompte', $numero);
    }

    /**
     * Scope local pour récupérer les comptes d'un client basé sur le téléphone
     *
     * @param Builder $query
     * @param string $telephone
     * @return Builder
     */
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client.user', function (Builder $q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

}
