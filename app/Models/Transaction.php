<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $table = "transactions";

    protected $fillable = [
        "montant",
        "type",
        "description",
        "compte_id",
        "date_transaction"
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
    ];

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteModel::class, 'compte_id');
    }
}