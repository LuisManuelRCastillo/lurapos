<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'customer_name',
        'original_amount', 'paid_amount', 'status', 'notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    // Monto restante calculado
    public function getRemainingAttribute(): float
    {
        return (float) $this->original_amount - (float) $this->paid_amount;
    }

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
